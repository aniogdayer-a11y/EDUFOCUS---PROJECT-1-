@if (session('success'))<div class="site-notice" role="status"><span aria-hidden="true">✓</span> {{ session('success') }}</div>@endif
@if ($errors->any())
    <div class="site-notice site-notice--error" role="alert"><strong>Please check these details:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
