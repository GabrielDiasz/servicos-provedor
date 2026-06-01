@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Selecione',
    'id' => null,
    'size' => 'md',
    'required' => false,
    'disabled' => false,
    'submitOnChange' => false,
    'busyLabel' => null,
])

@php
    $normalizedOptions = [];

    foreach ($options as $key => $option) {
        if (is_array($option)) {
            $value = (string) ($option['value'] ?? $key);
            $label = (string) ($option['label'] ?? $option['name'] ?? $value);
        } else {
            $value = (string) $key;
            $label = (string) $option;
        }

        $normalizedOptions[] = [
            'value' => $value,
            'label' => $label,
        ];
    }

    $selectedValue = old($name, $selected);
    $selectedValue = is_null($selectedValue) ? '' : (string) $selectedValue;
    $selectedOption = collect($normalizedOptions)->firstWhere('value', $selectedValue);
    $selectedLabel = $selectedOption['label'] ?? $placeholder;
    $panelOptions = array_merge([
        [
            'value' => '',
            'label' => $placeholder,
        ],
    ], $normalizedOptions);
    $inputId = $id ?? $name.'-'.\Illuminate\Support\Str::random(8);
@endphp

<div
    {{ $attributes->merge(['class' => 'sgp-select']) }}
    x-data="{
        open: false,
        panelStyle: '',
        panelPlacement: 'bottom',
        value: @js($selectedValue),
        label: @js($selectedLabel),
        options: @js($normalizedOptions),
        placeholder: @js($placeholder),
        disabled: @js($disabled),
        submitOnChange: @js($submitOnChange),
        busyLabel: @js($busyLabel),
        init() {
            const found = this.options.find((option) => String(option.value) === String(this.value));
            this.label = found ? found.label : this.placeholder;
        },
        openPanel() {
            if (this.disabled) return;

            this.open = true;
            this.$nextTick(() => this.updatePanelPosition());
        },
        togglePanel() {
            if (this.disabled) return;

            if (this.open) {
                this.open = false;
                return;
            }

            this.openPanel();
        },
        updatePanelPosition() {
            const trigger = this.$refs.trigger;
            if (!trigger) return;

            const rect = trigger.getBoundingClientRect();
            const panelWidth = Math.max(rect.width, 220);
            const safeLeft = Math.max(8, Math.min(rect.left, window.innerWidth - panelWidth - 8));
            const maxHeight = 240;

            let top = rect.bottom + 8;
            this.panelPlacement = 'bottom';

            if (top + maxHeight > window.innerHeight && rect.top - maxHeight - 8 > 8) {
                top = rect.top - maxHeight - 8;
                this.panelPlacement = 'top';
            }

            this.panelStyle = `position:fixed;top:${top}px;left:${safeLeft}px;width:${panelWidth}px;max-height:${maxHeight}px;`;
        },
        choose(option) {
            if (this.disabled) return;

            this.value = String(option.value ?? '');
            this.label = option.label ?? this.placeholder;
            this.$refs.select.value = this.value;
            this.$refs.select.dispatchEvent(new Event('input', { bubbles: true }));
            this.$refs.select.dispatchEvent(new Event('change', { bubbles: true }));
            this.open = false;

            if (this.submitOnChange && this.$refs.select.form) {
                window.dispatchEvent(new CustomEvent('busy-start', {
                    detail: { label: this.busyLabel || 'Salvando...' }
                }));

                setTimeout(() => this.$refs.select.form.submit(), 60);
            }
        },
        isSelected(option) {
            return String(option.value) === String(this.value);
        },
    }"
    x-on:keydown.escape.window="open = false"
    x-on:click.window="
        if (!open) return;
        if ($refs.trigger && $refs.trigger.contains($event.target)) return;
        if ($refs.panel && $refs.panel.contains($event.target)) return;
        open = false;
    "
    x-on:resize.window="open && updatePanelPosition()"
    x-on:scroll.window="open && updatePanelPosition()"
>
    <select
        x-ref="select"
        id="{{ $inputId }}"
        name="{{ $name }}"
        class="sr-only"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($normalizedOptions as $option)
            <option value="{{ $option['value'] }}" @selected($selectedValue === $option['value'])>
                {{ $option['label'] }}
            </option>
        @endforeach
    </select>

    <button
        x-ref="trigger"
        type="button"
        class="sgp-select-trigger {{ $size === 'sm' ? 'sgp-select-trigger-sm' : '' }}"
        x-on:click.stop="togglePanel()"
        x-on:keydown.enter.prevent="togglePanel()"
        x-on:keydown.space.prevent="togglePanel()"
        x-bind:aria-expanded="open.toString()"
        aria-haspopup="listbox"
        aria-controls="{{ $inputId }}-panel"
        @if ($disabled) disabled @endif
    >
        <span class="sgp-select-value" x-text="label"></span>
        <svg class="sgp-select-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            x-on:click.stop
            x-ref="panel"
            id="{{ $inputId }}-panel"
            role="listbox"
            class="sgp-select-panel"
            x-bind:style="panelStyle"
        >
            @foreach ($panelOptions as $option)
                <button
                    type="button"
                    role="option"
                    x-on:click="choose(@js($option))"
                    x-bind:aria-selected="isSelected(@js($option)).toString()"
                    class="sgp-select-option"
                    :class="isSelected(@js($option)) ? 'is-selected' : ''"
                >
                    <span>{{ $option['label'] }}</span>
                </button>
            @endforeach
        </div>
    </template>
</div>
