#!/usr/bin/env python3

import os
import sys
import json
import shutil
import subprocess
import zipfile
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional, Tuple


class AutoGrading:

    BASE_TREE = Path(__file__).resolve().parent
    BASE_PROJECT = BASE_TREE / "base-project"
    TESTING_DIR = BASE_TREE / "testing-project"
    WORKING_DIR = BASE_TREE/ "working-project"
    TMP_DIR = Path("/tmp/autograding")
    RESULTS_DIR = Path("/tmp/autograding_results")

    COMPONENTS_TO_REPLACE = ["app", "routes", "resources"]

    def __init__(self, zip_file: str, student_name: str = "Anonymous"):
        self.zip_file = Path(zip_file)
        self.student_name = student_name
        self.submission_id = datetime.now().strftime("%Y%m%d_%H%M%S")
        self.working_project = None
        self.results = {}

        self._ensure_paths_exist()

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
            shutil.copytree(project_folder, self.WORKING_DIR)

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
            for component in self.COMPONENTS_TO_REPLACE:
                source = self._find_component_path(self.TMP_DIR / f"extract_{self.submission_id}", component)
                destination = self.TESTING_DIR / component

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

    def _run_tests(self) -> Optional[str]:
        self._log("\n=== Run Tests ===")

        try:
            if not (self.working_project / "composer.json").exists():
                self._log("ERROR: composer.json not found")
                return None

            self._log("Running: php artisan test --json")

            result = subprocess.run(
                ["php", "artisan", "test", "--json"],
                cwd=self.working_project,
                capture_output=True,
                text=True,
                timeout=300
            )

            if result.returncode != 0 and result.returncode != 1:
                self._log(f"WARNING: Command returned code: {result.returncode}")
                if result.stderr:
                    self._log(f"ERROR STDOUT: {result.stdout.strip()}")
                    self._log(f"ERROR STDERR: {result.stderr.strip()}")
                else:
                    self._log("ERROR: No stderr from command, inspect Laravel logs or artisan output.")

            output = result.stdout or result.stderr
            if not output.strip():
                self._log("ERROR: No output from test command")
                return None

            self._log("OK: Tests executed, output captured")

            return output

        except subprocess.TimeoutExpired:
            self._log("ERROR: Timeout while running tests (>300s)")
            return None
        except FileNotFoundError:
            self._log("ERROR: PHP not found. Install with: sudo apt-get install php8.1 php8.1-cli")
            return None
        except Exception as e:
            self._log(f"ERROR: Failed to run tests: {e}")
            return None

    def _parse_results(self, test_output: str) -> Dict:
        self._log("\n=== Parse Results ===")

        try:
            data = json.loads(test_output)
            self._log("OK: JSON output parsed successfully")

            return self._analyze_results(data)

        except json.JSONDecodeError:
            self._log("WARNING: Output is not valid JSON, trying text parsing...")
            return self._parse_text_output(test_output)

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
                results["tests"].append({
                    "name": test_name,
                    "status": test_data.get("status", "unknown"),
                    "message": test_data.get("message", ""),
                })

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

        if results["tests"] and len(results["tests"]) <= 20:
            self._log("Test Details:")
            for test in results["tests"]:
                status_str = "PASS" if test["status"] == "passed" else "FAIL"
                self._log(f"  [{status_str}] {test['name']}")
                if test["message"]:
                    self._log(f"       -> {test['message'][:100]}")

        self._log("=" * 60 + "\n")

    def _save_results(self, results: Dict):
        result_file = (
            self.RESULTS_DIR /
            f"submission_{self.submission_id}.json"
        )

        result_data = {
            "student_name": self.student_name,
            "submission_id": self.submission_id,
            "timestamp": datetime.now().isoformat(),
            "results": results,
            "working_project_path": str(self.working_project)
        }

        with open(result_file, 'w') as f:
            json.dump(result_data, f, indent=2)

        self._log(f"Results saved to: {result_file}")

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

        extract_path = self._extract_zip()
        if not extract_path:
            return False

        if not self._copy_base_project():
            return False

        if not self._replace_components():
            return False

        test_output = self._run_tests()
        if not test_output:
            self._log("WARNING: No test output received")
            return False

        results = self._parse_results(test_output)

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

    if len(sys.argv) < 2:
        print("AutoGrading - Python Script")
        print("\nUsage:")
        print("  python3 main.py <zip_path> [student_name]")
        print("\nExample:")
        print("  python3 main.py /tmp/submission.zip 'John Silva'")
        sys.exit(1)

    zip_file = sys.argv[1]
    student_name = sys.argv[2] if len(sys.argv) > 2 else "Anonymous"

    exe = AutoGrading(zip_file, student_name)
    success = exe.run()

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
