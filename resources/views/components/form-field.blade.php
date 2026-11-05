@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'options' => null,
    'rows' => 5,
    'placeholder' => null,
    'hint' => null,
])

@php
    $id = 'campo-'.$name;
    $hasError = $errors->has($name);
    $describedBy = trim(($hasError ? $id.'-erro ' : '').($hint ? $id.'-ajuda' : ''));
@endphp

<div {{ $attributes->class(['form-field', 'has-error' => $hasError]) }}>
    <label for="{{ $id }}" class="form-field__label">
        {{ $label }}@if ($required)<span class="text-primary-500" aria-hidden="true"> *</span>@endif
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="form-field__control" @required($required)
                  @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                  @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                  @if ($hasError) aria-invalid="true" @endif>{{ old($name) }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="form-field__control" @required($required)
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                @if ($hasError) aria-invalid="true" @endif>
            <option value="">{{ $placeholder ?? __('Seleccione…') }}</option>
            @foreach ($options ?? [] as $value => $optionLabel)
                <option value="{{ $value }}" @selected((string) old($name) === (string) $value)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'file')
        <input id="{{ $id }}" type="file" name="{{ $name }}" class="form-field__control form-field__control--file" @required($required)
               {{ $attributes->only('accept') }}
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               @if ($hasError) aria-invalid="true" @endif>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}" class="form-field__control" @required($required)
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif
               {{ $attributes->only(['autocomplete', 'inputmode']) }}
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               @if ($hasError) aria-invalid="true" @endif>
    @endif

    @if ($hint)
        <p id="{{ $id }}-ajuda" class="form-field__hint">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-erro" class="form-field__error" role="alert">{{ $message }}</p>
    @enderror
</div>
