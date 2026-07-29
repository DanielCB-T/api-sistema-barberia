<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Helper centralizado para guardar/borrar/leer imágenes subidas por el
 * usuario (servicios, productos, sucursales, noticias, avatares).
 *
 * Los archivos se guardan en storage/app/public/{carpeta}/... y en la base
 * de datos solo se guarda la ruta relativa (ej. "products/abc123.jpg").
 * Para que sean accesibles por HTTP hace falta el enlace simbólico:
 *   php artisan storage:link
 */
class ImageStorage
{
    /**
     * Guarda un archivo subido en storage/app/public/{folder} y regresa la
     * ruta relativa que se debe guardar en la base de datos.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->storeAs($folder, $filename, 'public');

        return "{$folder}/{$filename}";
    }

    /**
     * Borra un archivo previamente guardado (ej. al reemplazar la imagen o
     * al eliminar el registro). No falla si la ruta es nula o no existe.
     */
    public static function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Convierte una ruta relativa guardada en la base de datos en una URL
     * completa que el frontend puede usar directamente en un <img src>.
     */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Si ya es una URL completa (por compatibilidad con datos viejos
        // sembrados por el seeder con links de Unsplash), se regresa tal cual.
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
