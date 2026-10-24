 <x-admin.tabla.contenedor class="admin-table-card">
     <section class="p-3 p-lg-4" aria-labelledby="multimedia-titulo">

         {{-- Cabecera de la Sección --}}
         <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
             <div>
                 <h2 id="multimedia-titulo" class="h5 mb-1 text-white fw-semibold">
                     Recursos registrados
                 </h2>
                 <p class="text-muted small mb-0">
                     Imágenes, pósteres y vídeos asociados a esta película.
                 </p>
             </div>
             <span class="badge text-bg-secondary align-self-start px-3 py-2">
                 {{ $multimedia->total() }}
                 {{ $multimedia->total() === 1 ? 'recurso' : 'recursos' }}
             </span>
         </div>

         {{-- Lista de Recursos --}}
         @forelse ($multimedia as $recurso)
         <article class="card border border-secondary border-opacity-25 rounded-3 p-3 mb-3 bg-transparent shadow-sm position-relative">
             <div class="row g-3 align-items-center">

                 {{-- 1. Vista Previa (Miniatura Uniforme) --}}
                 <div class="col-12 col-md-3 col-lg-2">
                     @if (in_array($recurso->tipo, ['POSTER', 'IMAGEN']))
                     <div class="bg-black rounded overflow-hidden position-relative border border-secondary border-opacity-10" style="height: 130px;">
                         <img src="{{ str_starts_with($recurso->url, 'peliculas/') ? asset('storage/' . $recurso->url) : $recurso->url }}"
                             alt="{{ $recurso->texto_alternativo ?: 'Imagen de ' . $pelicula->titulo }}"
                             class="w-100 h-100 object-fit-cover"
                             loading="lazy">
                     </div>
                     @else
                     <div class="bg-black rounded overflow-hidden position-relative border border-secondary border-opacity-10 d-flex flex-column align-items-center justify-content-center" style="height: 130px;">
                         <span class="material-symbols-outlined text-danger fs-1">play_circle</span>
                         <span class="small text-muted mt-1">
                             {{ $recurso->tipo === 'TRAILER' ? 'Tráiler' : 'Vídeo' }}
                         </span>
                     </div>
                     @endif
                 </div>

                 {{-- 2. Información del Recurso --}}
                 <div class="col-12 col-md-7 col-lg-8">
                     <div class="d-flex flex-wrap gap-2 mb-2">
                         <span class="badge text-bg-dark border border-secondary border-opacity-50 text-uppercase">
                             {{ $recurso->tipo }}
                         </span>

                         @if ($recurso->es_principal)
                         <span class="badge text-bg-info text-dark fw-semibold">
                             Recurso principal
                         </span>
                         @endif

                         @if ($recurso->activo)
                         <span class="badge text-bg-success">Activo</span>
                         @else
                         <span class="badge text-bg-secondary">Inactivo</span>
                         @endif
                     </div>

                     <div class="small mb-1 text-truncate">
                         <strong class="text-secondary">URL:</strong>
                         <a href="{{ str_starts_with($recurso->url, 'peliculas/') ? asset('storage/' . $recurso->url) : $recurso->url }}"
                             target="_blank" rel="noopener noreferrer" class="text-info text-decoration-none text-break">
                             {{ $recurso->url }}
                         </a>
                     </div>

                     <div class="small mb-1">
                         <strong class="text-secondary">Texto alternativo:</strong>
                         <span class="text-white-50">
                             {{ $recurso->texto_alternativo ?: 'No definido' }}
                         </span>
                     </div>

                     <div class="small text-secondary">
                         Orden de aparición: <span class="text-white fw-medium">{{ $recurso->orden }}</span>
                     </div>
                 </div>

                 {{-- 3. Acciones (Botones de Iconos Optimizados) --}}
                 <div class="col-12 col-md-2 col-lg-2 d-flex justify-content-md-end align-items-center gap-2 mt-3 mt-md-0 pt-2 pt-md-0 border-top border-secondary border-opacity-10 border-top-md-0">

                     {{-- Botón Editar --}}
                     <a href="{{ route('admin.peliculas.multimedia.edit', [$pelicula, $recurso]) }}"
                         class="btn btn-sm btn-dark border border-secondary border-opacity-25 text-info p-2 d-flex align-items-center justify-content-center shadow-none"
                         data-bs-toggle="tooltip"
                         data-bs-placement="top"
                         title="Editar">
                         <span class="material-symbols-outlined fs-5">edit</span>
                     </a>

                     {{-- Botón Desactivar --}}
                     @if ($recurso->activo)
                     <form method="POST"
                         action="{{ route('admin.peliculas.multimedia.desactivar', [$pelicula, $recurso]) }}"
                         onsubmit="return confirm('¿Deseas desactivar este recurso? El archivo se conservará.')"
                         class="d-inline m-0">
                         @csrf
                         @method('PATCH')
                         <button type="submit"
                             class="btn btn-sm btn-dark border border-secondary border-opacity-25 text-warning p-2 d-flex align-items-center justify-content-center shadow-none"
                             data-bs-toggle="tooltip"
                             data-bs-placement="top"
                             title="Desactivar recurso">
                             <span class="material-symbols-outlined fs-5">block</span>
                         </button>
                     </form>
                     @endif

                 </div>

             </div>
         </article>
         @empty
         <div class="text-center py-5 border border-secondary border-opacity-25 rounded-3 bg-dark bg-opacity-25">
             <span class="material-symbols-outlined fs-1 mb-2 text-secondary">perm_media</span>
             <p class="text-muted mb-0">No hay recursos multimedia registrados para esta película todavía.</p>
         </div>
         @endforelse



         {{-- Navegación entre páginas --}}
         @if ($multimedia->hasPages())
         <div class="mt-4">
             {{ $multimedia->links() }}
         </div>
         @endif

     </section>
 </x-admin.tabla.contenedor>