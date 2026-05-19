#!/usr/bin/env python3

import os
import sys
import json
import argparse
import shutil
import subprocess
import zipfile
import xml.etree.ElementTree as ET
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional, Tuple


class AutoGrading:

    BASE_TREE = Path(__file__).resolve().parent
    BASE_PROJECT = BASE_TREE / "base-project"
    TESTING_DIR = BASE_TREE / "testing-project"
    WORKING_DIR = BASE_TREE / "working-project"
    TMP_DIR = Path("/tmp/autograding")
    RESULTS_DIR = Path("/tmp/autograding_results")

    DEFAULT_COMPONENTS = ["app", "routes", "resources"]

    def __init__(
        self,
        zip_file: str,
        student_name: str = "Anonymous",
        *,
        result_json_path: Optional[str] = None,
        components_json_path: Optional[str] = None,
        storage_work_dir: Optional[str] = None,
        archive_submitted_zip_path: Optional[str] = None,
        process_config_path: Optional[str] = None,
    ):
        self.zip_file = Path(zip_file)
        self.student_name = student_name
        self.submission_id = datetime.now().strftime("%Y%m%d_%H%M%S")
        self.working_project = None
        self.results = {}
        self.result_json_path = Path(result_json_path) if result_json_path else None
        self.storage_work_dir = (
            Path(storage_work_dir).expanduser().resolve() if storage_work_dir else None
        )
        self.archive_submitted_zip_path = (
            Path(archive_submitted_zip_path).expanduser().resolve()
            if archive_submitted_zip_path
            else None
        )
        self.process_config_path = (
            Path(process_config_path).expanduser().resolve()
            if process_config_path
            else None
        )
        self.process_config: Dict = {}
        self.junit_report_file: Optional[Path] = None

        if components_json_path:
            with open(components_json_path, "r", encoding="utf-8") as cf:
                loaded = json.load(cf)
            if not isinstance(loaded, list) or not all(isinstance(x, str) for x in loaded):
                raise ValueError("components JSON must be a list of strings")
            self.components_to_replace = loaded
        elif os.environ.get("AUTOGRADING_COMPONENTS"):
            self.components_to_replace = json.loads(os.environ["AUTOGRADING_COMPONENTS"])
        else:
            self.components_to_replace = list(self.DEFAULT_COMPONENTS)

        if self.process_config_path:
            if self.process_config_path.is_file():
                with open(self.process_config_path, "r", encoding="utf-8") as pf:
                    loaded = json.load(pf)
                self.process_config = loaded if isinstance(loaded, dict) else {}
            else:
                self._log(
                    f"WARNING: --process-config não encontrado: {self.process_config_path}"
                )

        self._ensure_paths_exist()

    def _php_binary(self) -> str:
        exe = (os.environ.get("AUTOGRADING_PHP_BINARY") or "").strip()
        return exe if exe else (shutil.which("php") or "php")

    def _report_base_dir(self) -> Path:
        if self.storage_work_dir:
            return self.storage_work_dir
        if self.result_json_path:
            return self.result_json_path.resolve().parent
        return self.RESULTS_DIR

    def _junit_report_dest_path(self) -> Path:
        return self._report_base_dir() / "report.xml"

    def _junit_local_path(self) -> Path:
        return (self.working_project / "junit_autograding.xml").resolve()

    def _archive_submitted_zip(self) -> None:
        if not self.archive_submitted_zip_path:
            return
        dest = self.archive_submitted_zip_path
        try:
            dest.parent.mkdir(parents=True, exist_ok=True)
            if self.zip_file.resolve() == dest.resolve():
                self._log(f"OK: ZIP já em {dest}")
                return
            shutil.copy2(self.zip_file, dest)
            self._log(f"OK: ZIP guardado em {dest}")
        except OSError as e:
            self._log(f"WARNING: cópia do ZIP falhou: {e}")

    def _ensure_vendor(self) -> bool:
        if not self.working_project:
            return False
        autoload = self.working_project / "vendor" / "autoload.php"
        if autoload.is_file():
            return True
        composer = shutil.which("composer")
        if not composer:
            self._log("ERROR: vendor/ em falta e Composer não está no PATH")
            return False
        php = self._php_binary()
        self._log("vendor/ ausente; a executar composer install…")
        r = subprocess.run(
            [php, composer, "install", "--no-interaction", "--prefer-dist"],
            cwd=self.working_project,
            capture_output=True,
            text=True,
            timeout=900,
        )
        if r.returncode != 0:
            self._log(f"ERROR: composer install falhou:\n{(r.stderr or r.stdout)[-3000:]}")
            return False
        return autoload.is_file()

    @staticmethod
    def _xml_tag(el: ET.Element) -> str:
        t = el.tag
        return t.split("}", 1)[-1] if t.startswith("{") else t

    def _normalize_path_list(self, raw) -> List[str]:
        if not isinstance(raw, list):
            return []
        out: List[str] = []
        for p in raw:
            rel = str(p).strip().strip("/\\")
            if rel:
                out.append(rel.replace("\\", "/"))
        return out

    def _discover_test_paths(self) -> List[str]:
        """Pastas em tests/ com autograding.json (tests, tests1, tests2, …)."""
        if not self.working_project:
            return []
        tests_root = self.working_project / "tests"
        found: List[str] = []
        if tests_root.is_dir():
            for child in sorted(tests_root.iterdir()):
                if child.is_dir() and (child / "autograding.json").is_file():
                    found.append(f"tests/{child.name}")
        return found

    def _resolve_run_test_paths(self) -> List[str]:
        """União de all_test_paths, test_paths e pastas descobertas no projeto."""
        cfg = self.process_config if isinstance(self.process_config, dict) else {}
        all_paths = self._normalize_path_list(cfg.get("all_test_paths"))
        configured = all_paths if all_paths else self._normalize_path_list(cfg.get("test_paths"))
        discovered = self._discover_test_paths()
        merged: List[str] = []
        for p in configured + discovered:
            if p not in merged:
                merged.append(p)
        return merged

    def _relative_project_path(self, abs_path: str) -> str:
        if not abs_path or not self.working_project:
            return ""
        try:
            return str(Path(abs_path).resolve().relative_to(self.working_project.resolve())).replace(
                "\\", "/"
            )
        except ValueError:
            return str(abs_path).replace("\\", "/")

    @staticmethod
    def _test_matches_path_prefixes(test: Dict, prefixes: List[str]) -> bool:
        if not prefixes:
            return True
        file_path = (test.get("file") or "").replace("\\", "/").lstrip("./")
        for prefix in prefixes:
            pre = prefix.strip("/")
            if not pre:
                continue
            if file_path and (file_path == pre or file_path.startswith(pre + "/")):
                return True
        name = (test.get("name") or "").replace("\\", "/")
        for prefix in prefixes:
            pre = prefix.strip("/")
            if pre and (f"/{pre}/" in name or name.startswith(pre + "/")):
                return True
        return False

    def _filter_results_by_paths(self, results: Dict, prefixes: List[str]) -> Dict:
        if not prefixes:
            return results
        tests = results.get("tests") or []
        if not isinstance(tests, list):
            return results
        filtered = [t for t in tests if self._test_matches_path_prefixes(t, prefixes)]
        passed = failed = errors = skipped = 0
        duration = 0.0
        for t in filtered:
            st = t.get("status", "")
            if st == "passed":
                passed += 1
            elif st == "skipped":
                skipped += 1
            elif st == "failed":
                failed += 1
            else:
                errors += 1
        total = len(filtered)
        summary = {
            "total_tests": total,
            "successful": passed,
            "failed": failed,
            "errors": errors,
            "skipped": skipped,
            "duration": duration,
            "success_rate": (passed / total * 100) if total > 0 else 0.0,
        }
        out = dict(results)
        out["summary"] = summary
        out["tests"] = filtered
        out["filtered_for_paths"] = prefixes
        return out

    def _finalize_report_xml(self, local_xml: Path, dest_xml: Path) -> None:
        dest_xml.parent.mkdir(parents=True, exist_ok=True)
        if local_xml.is_file():
            try:
                shutil.copy2(local_xml, dest_xml)
                self.junit_report_file = dest_xml.resolve()
                self._log(f"OK: Relatório JUnit em {dest_xml}")
            except OSError as e:
                self._log(f"WARNING: cópia report.xml falhou: {e}")
        else:
            self._log("WARNING: PHPUnit não gerou junit_autograding.xml")

    def _ensure_paths_exist(self):
        self.TMP_DIR.mkdir(parents=True, exist_ok=True)
        self.RESULTS_DIR.mkdir(parents=True, exist_ok=True)
        self._log("Directories verified")

    def _log(self, message: str):
        timestamp = datetime.now().strftime("%H:%M:%S")
        print(f"[{timestamp}] {message}")

    def _validate_zip(self) -> bool:
        self._log(f"Checking ZIP file: {self.zip_file}")

        if not self.zip_file.exists():
            self._log(f"ERROR: ZIP file not found: {self.zip_file}")
            return False

        if not zipfile.is_zipfile(self.zip_file):
            self._log("ERROR: File is not a valid ZIP")
            return False

        self._log("OK: ZIP is valid")
        return True

    def _validate_base_project(self) -> bool:
        self._log(f"Checking base project: {self.BASE_PROJECT}")

        if not self.BASE_PROJECT.exists():
            self._log(f"ERROR: Base project not found: {self.BASE_PROJECT}")
            return False

        if not (self.BASE_PROJECT / "composer.json").exists():
            self._log("ERROR: Base project does not appear to be valid Laravel")
            return False

        self._log("OK: Base project is valid")
        return True

    def _find_project_root(self, root: Path, max_depth=3) -> Optional[Path]:
        if (root / "composer.json").exists() or (root / "artisan").exists():
            return root
        if max_depth > 0:
            for item in root.iterdir():
                if item.is_dir():
                    found = self._find_project_root(item, max_depth - 1)
                    if found:
                        return found
        return None

    def _find_project_folder(self, extract_path: Path) -> Optional[Path]:
        self._log(f"Looking for project folders in: {extract_path}")
        project_root = self._find_project_root(extract_path)
        if project_root:
            self._log(f"Found project root: {project_root}")
            return project_root
        else:
            self._log("ERROR: No project folder found in extracted ZIP")
            return None

    def _copy_base_project(self) -> bool:
        self._log("\n=== Copy Base Project ===")

        try:
            if self.WORKING_DIR.exists():
                self._log(f"Removing previous directory: {self.WORKING_DIR}")
                shutil.rmtree(self.WORKING_DIR)

            extract_path = self.TMP_DIR / f"extract_{self.submission_id}"
            project_folder = self._find_project_folder(extract_path)
            if not project_folder:
                return False

            self._log(f"Copying {project_folder} -> {self.WORKING_DIR}")
            shutil.copytree(self.BASE_PROJECT, self.WORKING_DIR)

            self.working_project = self.WORKING_DIR
            self._log("OK: Working project copied successfully")
            return True

        except Exception as e:
            self._log(f"ERROR: Failed to copy base project: {e}")
            return False

    def _extract_zip(self) -> Optional[Path]:
        self._log("\n=== Extract ZIP ===")

        try:
            extract_path = self.TMP_DIR / f"extract_{self.submission_id}"
            extract_path.mkdir(parents=True, exist_ok=True)

            self._log(f"Extracting {self.zip_file}")
            self._log(f"To: {extract_path}")

            with zipfile.ZipFile(self.zip_file, 'r') as zip_ref:
                zip_ref.extractall(extract_path)

            files_count = len(list(extract_path.rglob("*")))
            self._log(f"OK: Extracted {files_count} files")

            return extract_path

        except Exception as e:
            self._log(f"ERROR: Failed to extract ZIP: {e}")
            return None

    def _find_component_path(self, root: Path, name: str) -> Optional[Path]:
        for path in root.rglob(name):
            if path.is_dir():
                return path
        return None

    def _replace_components(self) -> bool:
        self._log("\n=== Replace Components ===")

        if not self.TESTING_DIR.exists():
            self._log(f"WARNING: Testing directory does not exist: {self.TESTING_DIR}")
            return False

        if not any(self.TESTING_DIR.iterdir()):
            shutil.rmtree(self.TESTING_DIR)
            shutil.copytree(self.BASE_PROJECT, self.TESTING_DIR)

        try:
            for component in self.components_to_replace:
                source = self._find_component_path(self.TMP_DIR / f"extract_{self.submission_id}", component)
                destination = self.working_project / component

                if not source:
                    self._log(f"WARNING: Component not found in ZIP: {component}")
                    continue

                self._log(f"Replacing {component}")

                if destination.exists():
                    shutil.rmtree(destination)
                    self._log(f"  - Removed previous {component}")

                shutil.copytree(source, destination)
                self._log(f"  OK: {component} replaced")

            
            self._log("OK: Components replaced successfully")
            return True

        except Exception as e:
            self._log(f"ERROR: Failed to replace components: {e}")
            return False
            

    def _run_tests(self) -> Tuple[Optional[str], Path]:
        self._log("\n=== Run Tests ===")

        dest_report = self._junit_report_dest_path()

        try:
            if not self.working_project or not (self.working_project / "composer.json").exists():
                self._log("ERROR: composer.json not found")
                return None, dest_report

            if not self._ensure_vendor():
                return None, dest_report

            local_xml = self._junit_local_path()
            if local_xml.exists():
                local_xml.unlink()

            php = self._php_binary()
            junit_arg = str(local_xml.resolve())
            phpunit_bin = self.working_project / "vendor" / "bin" / "phpunit"

            path_args: List[str] = []
            platform_mode = self.storage_work_dir is not None
            run_paths = self._resolve_run_test_paths()

            for rel in run_paths:
                full = (self.working_project / rel).resolve()
                if not full.exists():
                    self._log(
                        f"WARNING: pasta de testes inexistente no projeto: {full}"
                    )
                path_args.append(str(full))

            if platform_mode and not path_args:
                self._log(
                    "ERROR: process-config sem all_test_paths/test_paths válidos (correção na plataforma)."
                )
                return None, dest_report

            if phpunit_bin.is_file():
                cmd = [php, str(phpunit_bin), "--log-junit", junit_arg]
                cmd.extend(path_args)
                scope = " ".join(path_args) if path_args else "(suite default phpunit.xml)"
                self._log(f"Running: phpunit --log-junit … {scope}")
            else:
                cmd = [php, "artisan", "test"]
                cmd.extend(path_args)
                cmd.extend(["--log-junit", junit_arg])
                scope = " ".join(path_args) if path_args else "(suite default)"
                self._log(f"Running: artisan test … {scope}")
            self._log(f"Relatório final (storage): {dest_report}")

            result = subprocess.run(
                cmd,
                cwd=self.working_project,
                capture_output=True,
                text=True,
                timeout=300,
            )

            if result.returncode != 0 and result.returncode != 1:
                self._log(f"WARNING: Command returned code: {result.returncode}")

            parts = []
            if result.stdout:
                parts.append(result.stdout)
            if result.stderr:
                parts.append(result.stderr)
            output = "\n".join(parts)
            if not output.strip():
                self._log("ERROR: No output from test command")

            self._finalize_report_xml(local_xml, dest_report)

            if output.strip():
                self._log("OK: Tests executed, output captured")

            return output if output.strip() else None, dest_report

        except subprocess.TimeoutExpired:
            self._log("ERROR: Timeout while running tests (>300s)")
            return None, dest_report
        except FileNotFoundError:
            self._log("ERROR: PHP não encontrado (defina AUTOGRADING_PHP_BINARY se necessário)")
            return None, dest_report
        except Exception as e:
            self._log(f"ERROR: Failed to run tests: {e}")
            return None, dest_report

    def _merge_suite_autograding_json(self) -> None:
        """Mescla autograding.json de cada pasta executada para process_config.suite_configs."""
        if not self.working_project:
            return
        if not isinstance(self.process_config, dict):
            self.process_config = {}
        run_paths = self._resolve_run_test_paths()
        if not run_paths:
            return
        suites: Dict[str, Dict] = {}
        for raw in run_paths:
            rel = str(raw).strip().strip("/\\")
            if not rel:
                continue
            cfg_path = (self.working_project / rel / "autograding.json").resolve()
            if cfg_path.is_file():
                try:
                    with open(cfg_path, "r", encoding="utf-8") as f:
                        data = json.load(f)
                    if isinstance(data, dict):
                        suites[rel] = data
                        self._log(f"OK: autograding.json carregado: {cfg_path}")
                except (OSError, json.JSONDecodeError) as e:
                    self._log(f"WARNING: autograding.json inválido em {cfg_path}: {e}")
            else:
                self._log(f"INFO: sem autograding.json em {cfg_path}")
        if suites:
            self.process_config["suite_configs"] = suites

    def _parse_junit_xml_results(self, junit_path: Path) -> Optional[Dict]:
        if not junit_path.is_file():
            return None
        try:
            tree = ET.parse(junit_path)
            root = tree.getroot()
        except (ET.ParseError, OSError):
            return None

        tests: List[Dict] = []
        passed = failed = errors = skipped = 0
        duration = 0.0

        for el in root.iter():
            if self._xml_tag(el) != "testcase":
                continue
            try:
                duration += float(el.get("time") or 0)
            except (TypeError, ValueError):
                pass
            name = el.get("name") or ""
            classname = el.get("classname") or el.get("class") or ""
            disp = f"{classname}::{name}" if classname else name
            rel_file = self._relative_project_path(el.get("file") or "")

            fail_nodes = [
                c for c in el
                if self._xml_tag(c) in ("failure", "error", "skipped")
            ]
            msg = ""
            status = "passed"
            if not fail_nodes:
                passed += 1
            else:
                c0 = fail_nodes[0]
                tag = self._xml_tag(c0)
                if tag == "skipped":
                    status = "skipped"
                    skipped += 1
                elif tag == "error":
                    status = "failed"
                    errors += 1
                else:
                    status = "failed"
                    failed += 1
                msg = (c0.get("message") or "").strip()
                body = (c0.text or "").strip()
                if body:
                    msg = f"{msg}\n{body}".strip() if msg else body

            row = {"name": disp, "status": status, "message": msg}
            if rel_file:
                row["file"] = rel_file
            tests.append(row)

        total = len(tests)
        summary = {
            "total_tests": total,
            "successful": passed,
            "failed": failed,
            "errors": errors,
            "skipped": skipped,
            "duration": duration,
            "success_rate": (passed / total * 100) if total > 0 else 0.0,
        }

        return {
            "type": "junit",
            "summary": summary,
            "tests": tests,
        }

    def _parse_results(self, test_output: str, junit_fallback: Optional[Path] = None) -> Dict:
        self._log("\n=== Parse Results ===")

        text = (test_output or "").strip()
        try:
            data = json.loads(text)
            self._log("OK: JSON output parsed successfully")
            return self._analyze_results(data)
        except json.JSONDecodeError:
            pass

        if junit_fallback:
            ju = self._parse_junit_xml_results(junit_fallback)
            if ju is not None:
                self._log("OK: Summary derived from JUnit XML")
                return ju

        self._log("WARNING: Output is not valid JSON, trying text parsing...")
        return self._parse_text_output(test_output or "")

    def _analyze_results(self, data: Dict) -> Dict:
        results = {
            "type": "json",
            "summary": {
                "total_tests": data.get("testCount", 0),
                "successful": data.get("successfulCount", 0),
                "failed": data.get("failedCount", 0),
                "errors": data.get("incompleteCount", 0),
                "skipped": data.get("skippedCount", 0),
                "duration": data.get("duration", 0),
            },
            "tests": []
        }

        total = results["summary"]["total_tests"]
        if total > 0:
            results["summary"]["success_rate"] = (
                results["summary"]["successful"] / total * 100
            )
        else:
            results["summary"]["success_rate"] = 0

        if "tests" in data:
            for test_name, test_data in data["tests"].items():
                row = {
                    "name": test_name,
                    "status": test_data.get("status", "unknown"),
                    "message": test_data.get("message", ""),
                }
                for fk in ("file", "filepath", "path"):
                    v = test_data.get(fk)
                    if isinstance(v, str) and v.strip():
                        row["file"] = v.strip().replace("\\", "/")
                        break
                results["tests"].append(row)

        return results

    def _parse_text_output(self, output: str) -> Dict:
        results = {
            "type": "text",
            "summary": {
                "total_tests": 0,
                "successful": 0,
                "failed": 0,
                "errors": 0,
                "skipped": 0,
                "duration": 0.0,
                "success_rate": 0
            },
            "tests": []
        }

        for line in output.split("\n"):
            if "passed" in line.lower():
                results["summary"]["successful"] += 1
            elif "failed" in line.lower():
                results["summary"]["failed"] += 1

        results["summary"]["total_tests"] = (
            results["summary"]["successful"] +
            results["summary"]["failed"]
        )

        if results["summary"]["total_tests"] > 0:
            results["summary"]["success_rate"] = (
                results["summary"]["successful"] /
                results["summary"]["total_tests"] * 100
            )

        test_lines = [line.strip() for line in output.split("\n") if line.strip()]
        for line in test_lines:
            if "test" in line.lower() and ("passed" in line.lower() or "failed" in line.lower()):
                if ":" in line:
                    parts = line.split(":", 1)
                    test_name = parts[0].strip()
                    status_part = parts[1].strip().lower()
                    status = "passed" if "passed" in status_part else "failed"
                    results["tests"].append({
                        "name": test_name,
                        "status": status,
                        "message": "",
                    })
                else:
                    status = "passed" if "passed" in line.lower() else "failed"
                    results["tests"].append({
                        "name": line,
                        "status": status,
                        "message": "",
                    })
        if not results["tests"]:
            for i in range(results["summary"]["successful"]):
                results["tests"].append({
                    "name": f"Test {i+1}",
                    "status": "passed",
                    "message": "",
                })
            for i in range(results["summary"]["failed"]):
                results["tests"].append({
                    "name": f"Test {results['summary']['successful'] + i + 1}",
                    "status": "failed",
                    "message": "",
                })

        return results

    def _display_results(self, results: Dict):
        self._log("\n" + "=" * 60)
        self._log("TEST RESULTS")
        self._log("=" * 60)

        summary = results["summary"]

        if summary['total_tests'] == 0:
            self._log("WARNING: No tests were executed or detected")
            self._log("Please check the submission and test configuration")
            return

        self._log(f"\nStudent: {self.student_name}")
        self._log(f"Submission ID: {self.submission_id}")
        self._log(f"Output Type: {results['type']}")

        self._log(f"\nSummary:")
        self._log(f"  - Total tests: {summary['total_tests']}")
        self._log(f"  - Passed: {summary['successful']}")
        self._log(f"  - Failed: {summary['failed']}")
        self._log(f"  - Errors: {summary['errors']}")
        self._log(f"  - Skipped: {summary.get('skipped', 0)}")
        self._log(f"  - Duration: {summary['duration']:.2f}s")

        success_rate = summary.get("success_rate", 0)
        if success_rate >= 50:
            status = "PARTIAL"
        else:
            status = "FAILED"

        self._log(f"\nStatus: {status}")
        self._log(f"Success Rate: {success_rate:.1f}%\n")

        if results["tests"]:
            self._log("Test Details:")
            for test in results["tests"]:
                status_str = "PASS" if test["status"] == "passed" else "FAIL"
                self._log(f"  [{status_str}] {test['name']}")
                if test.get("message"):
                    self._log(f"       -> {test['message'][:100]}")
                if test.get("logs") is not None:
                    logs = test["logs"]
                    if not isinstance(logs, str):
                        logs = json.dumps(logs, ensure_ascii=False)
                    self._log(f"       logs: {logs}")

        self._log("=" * 60 + "\n")

    def _save_results(self, results: Dict):
        if self.result_json_path:
            result_file = self.result_json_path
            result_file.parent.mkdir(parents=True, exist_ok=True)
        else:
            result_file = self.RESULTS_DIR / f"submission_{self.submission_id}.json"

        result_data = {
            "student_name": self.student_name,
            "submission_id": self.submission_id,
            "timestamp": datetime.now().isoformat(),
            "results": results,
            "working_project_path": str(self.working_project),
            "components_replaced": self.components_to_replace,
        }
        if self.process_config:
            result_data["autograding_process_config"] = self.process_config
        jr = self.junit_report_file if self.junit_report_file else self._junit_report_dest_path()
        if Path(jr).is_file():
            rp = str(jr.resolve())
            result_data["report_xml_path"] = rp
            result_data["junit_report_path"] = rp

        with open(result_file, 'w', encoding='utf-8') as f:
            json.dump(result_data, f, indent=2)

        self._log(f"Results saved to: {result_file}")
        print(f"AUTOGRADING_RESULT_JSON={result_file}", flush=True)

    def _cleanup(self, extract_path: Path):
        self._log("\n=== Cleanup ===")

        try:
            if extract_path.exists():
                shutil.rmtree(extract_path)
                self._log(f"Removed: {extract_path}")
            self._log("OK: Cleanup completed")
        except Exception as e:
            self._log(f"WARNING: Cleanup error: {e}")

    def run(self) -> bool:
        self._log("\n" + "=" * 60)
        self._log("STARTING AUTOGRADING")
        self._log("=" * 60)

        if not self._validate_zip() or not self._validate_base_project():
            return False

        self._archive_submitted_zip()

        extract_path = self._extract_zip()
        if not extract_path:
            return False

        if not self._copy_base_project():
            return False

        if not self._replace_components():
            return False

        self._merge_suite_autograding_json()

        test_output, _ = self._run_tests()
        local_junit = self._junit_local_path()
        if not (test_output or "").strip() and not local_junit.is_file():
            self._log("WARNING: No test output received")
            return False

        results = self._parse_results(test_output or "", junit_fallback=local_junit)
        total = len(results.get("tests") or [])
        self._log(f"OK: {total} teste(s) agregados de todas as pastas configuradas")

        self._display_results(results)

        self._save_results(results)

        self._cleanup(extract_path)

        self._log("OK: AUTOGRADING COMPLETED SUCCESSFULLY")
        return True

class Clear:

    BASE_TREE = Path(__file__).resolve().parent
    BASE_PROJECT = BASE_TREE / "base-project"
    TESTING_DIR = BASE_TREE / "testing-project"
    WORKING_DIR = BASE_TREE/ "working-project"
    TMP_DIR = Path("/tmp/autograding")

    def _log(self, message: str):
        timestamp = datetime.now().strftime("%H:%M:%S")
        print(f"[{timestamp}] {message}")

    def __init__(self):
        self._log("Starting clear process")
        dirs_to_clear = [
            AutoGrading.TMP_DIR,
            AutoGrading.TESTING_DIR,
            AutoGrading.WORKING_DIR,
        ]
    
        for directory in dirs_to_clear:
            if directory.exists():
                self._log(f"Clearing contents of: {directory}")
                for item in directory.iterdir():
                    if item.is_file():
                        item.unlink()
                    elif item.is_dir():
                        shutil.rmtree(item)
                self._log(f"Contents cleared: {directory}")
            else:
                self._log(f"Not found (skipping): {directory}")

        self._log("Clear process completed")


def main():

    if len(sys.argv) > 1 and sys.argv[1] == "--clear":
        Clear()
        sys.exit(0)

    parser = argparse.ArgumentParser(description="AutoGrading — integração com a plataforma de submissões")
    parser.add_argument("zip_file", help="Caminho absoluto para o .zip da submissão")
    parser.add_argument(
        "student_name",
        nargs="?",
        default="Anonymous",
        help="Nome do estudante (para logs)",
    )
    parser.add_argument(
        "--result-json",
        dest="result_json",
        default=None,
        help="Ficheiro onde gravar o JSON completo dos resultados",
    )
    parser.add_argument(
        "--components-json",
        dest="components_json",
        default=None,
        help='JSON array, ex.: ["app","routes","resources"] — pastas a substituir no projeto de teste',
    )
    parser.add_argument(
        "--storage-work-dir",
        dest="storage_work_dir",
        default=None,
        help="Pasta absoluta do Laravel para report.xml e result.json (ex.: .../autograding/submission-N)",
    )
    parser.add_argument(
        "--archive-submitted-zip",
        dest="archive_submitted_zip",
        default=None,
        help="Caminho absoluto para gravar cópia do ZIP (ex.: .../submission-N/submission.zip)",
    )
    parser.add_argument(
        "--process-config",
        dest="process_config",
        default=None,
        help="JSON com test_paths, visibility e pesos (gravado pelo Laravel em cada submissão)",
    )
    args = parser.parse_args()

    exe = AutoGrading(
        args.zip_file,
        args.student_name,
        result_json_path=args.result_json,
        components_json_path=args.components_json,
        storage_work_dir=args.storage_work_dir,
        archive_submitted_zip_path=args.archive_submitted_zip,
        process_config_path=args.process_config,
    )
    success = exe.run()

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
