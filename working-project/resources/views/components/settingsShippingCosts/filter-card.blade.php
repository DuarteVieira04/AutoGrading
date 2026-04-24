<div {{ $attributes }}>
    <form method="GET" action="{{ $filterAction }}">
        <div class="flex justify-between space-x-3">
            <div class="grow flex flex-col space-y-2">
                <div>
                    <flux:input name="min_value_threshold" label="Min Value Threshold" class="grow" value="{{ $minValueThreshold }}"/>
                    <flux:input name="max_value_threshold" label="Max Value Threshold" class="grow" value="{{ $maxValueThreshold }}"/>
                    <flux:input name="shipping_cost" label="Shipping Cost" class="grow" value="{{ $shippingCost }}"/>
                </div>
            </div>
            <div class="grow-0 flex flex-col space-y-3 justify-start">
                <div class="pt-6">
                    <flux:button variant="primary" type="submit">Filter</flux:button>
                </div>
                <div>
                    <flux:button :href="$resetUrl">Cancel</flux:button>
                </div>
            </div>
        </div>
    </form>
</div>
