@props([
    'name',
    'label' => 'Imagen',
    'id' => null,
    'accept' => 'image/jpeg,image/png,image/webp',
    'currentSrc' => null,
    'currentLabel' => 'Imagen guardada actualmente',
    'help' => 'Selecciona una imagen para ver una vista previa antes de guardar.',
    'urlInput' => null,
    'required' => false,
])
@php
    $id = $id ?: $name;
    $hasError = $errors->has($name);
    $previewId = $id . '-preview';
@endphp
<div class="form-cm__group" data-form-image-upload data-input-id="{{ $id }}" data-preview-id="{{ $previewId }}" data-current-src="{{ $currentSrc ?? '' }}" @if($urlInput) data-url-input="{{ $urlInput }}" @endif>
    <label for="{{ $id }}" class="form-cm__label">
        <span>{{ $label }}</span>
        @if($required)<span class="required" aria-hidden="true">*</span><span class="visually-hidden">(obligatorio)</span>@endif
    </label>
    <input id="{{ $id }}" name="{{ $name }}" type="file" accept="{{ $accept }}" @if($required) required @endif aria-describedby="{{ $id }}-help {{ $id }}-status" class="form-cm__control {{ $hasError ? 'is-invalid' : '' }}" {{ $attributes }}>
    <div id="{{ $id }}-help" class="form-cm__text">{{ $help }}</div>
    @error($name)<div id="{{ $id }}-error" class="form-cm__feedback" role="alert">{{ $message }}</div>@enderror
    <div id="{{ $previewId }}" class="form-cm__file-preview mt-3" @if(!$currentSrc) hidden @endif>
        <div class="d-flex flex-wrap align-items-start gap-3">
            <img data-preview-image src="{{ $currentSrc ?? '' }}" alt="{{ $currentLabel }}" class="img-thumbnail" style="max-width:180px;max-height:220px;object-fit:contain" @if(!$currentSrc) hidden @endif>
            <div class="flex-grow-1">
                <p data-preview-status id="{{ $id }}-status" class="form-cm__text mb-2" role="status" aria-live="polite">{{ $currentSrc ? $currentLabel . '. Si no seleccionas otro archivo, se conservará.' : 'Todavía no hay una imagen seleccionada.' }}</p>
                <button type="button" class="btn btn-sm btn-outline-light" data-clear-file hidden>Descartar archivo seleccionado</button>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    function inicializar(campo) {
        if (campo.dataset.imageUploadReady === '1') return;
        campo.dataset.imageUploadReady = '1';
        const input = document.getElementById(campo.dataset.inputId);
        const preview = document.getElementById(campo.dataset.previewId);
        if (!input || !preview) return;
        const image = preview.querySelector('[data-preview-image]');
        const status = preview.querySelector('[data-preview-status]');
        const clear = preview.querySelector('[data-clear-file]');
        const currentSrc = campo.dataset.currentSrc || '';
        const urlInput = campo.dataset.urlInput ? document.getElementById(campo.dataset.urlInput) : null;
        let objectUrl = null;
        let revision = 0;

        function releaseObjectUrl() {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        function show(src, message) {
            const currentRevision = ++revision;
            preview.hidden = false;
            image.hidden = !src;
            if (!src) {
                image.removeAttribute('src');
                status.textContent = message;
                return;
            }
            image.onload = () => { if (currentRevision === revision) status.textContent = message; };
            image.onerror = () => { if (currentRevision === revision) status.textContent = 'No se pudo cargar la vista previa. Comprueba el archivo o la URL.'; };
            image.src = src;
            status.textContent = 'Cargando vista previa…';
        }
        function update() {
            releaseObjectUrl();
            clear.hidden = !input.files.length;
            if (input.files.length) {
                objectUrl = URL.createObjectURL(input.files[0]);
                const nombreArchivo = input.files[0].name;
                const ambasFuentes = urlInput && urlInput.value.trim();
                show(objectUrl, ambasFuentes
                    ? 'Archivo seleccionado: ' + nombreArchivo + '. No combines archivo y URL: elimina una de las dos fuentes antes de guardar.'
                    : 'Archivo seleccionado: ' + nombreArchivo + '. Aún no está guardado.');
                return;
            }
            if (urlInput && urlInput.value.trim()) {
                show(urlInput.value.trim(), 'Vista previa de la URL. Se guardará solo al enviar el formulario.');
                return;
            }
            if (currentSrc) {
                show(currentSrc, 'Imagen guardada actualmente. Si no seleccionas otra, se conservará.');
                return;
            }
            preview.hidden = true;
            show('', 'Todavía no hay una imagen seleccionada.');
            preview.hidden = true;
        }
        input.addEventListener('change', update);
        if (urlInput) urlInput.addEventListener('input', update);
        clear.addEventListener('click', () => { input.value = ''; update(); input.focus(); });
        window.addEventListener('beforeunload', releaseObjectUrl, { once: true });
        if (currentSrc || (urlInput && urlInput.value.trim())) update();
    }
    const start = () => document.querySelectorAll('[data-form-image-upload]').forEach(inicializar);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
</script>
