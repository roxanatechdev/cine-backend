@if ($errors->any())
    <div {{ $attributes->class(['form-cm__errors', 'alert', 'alert-danger']) }} role="alert" aria-labelledby="form-errors-title">
        <p id="form-errors-title" class="fw-semibold mb-1">Revisa los siguientes errores:</p>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
