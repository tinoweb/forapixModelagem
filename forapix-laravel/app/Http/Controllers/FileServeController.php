<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileServeController extends Controller
{
    /**
     * Serve arquivos de upload armazenados em storage/app/uploads/.
     * Rota: GET /uploads/{any}
     *
     * Funciona em qualquer hospedagem sem necessidade de storage:link.
     */
    public function serve(string $path): BinaryFileResponse
    {
        $fullPath = storage_path('app/uploads/' . $path);

        abort_unless(file_exists($fullPath), 404);

        // Segurança: impede path traversal
        $realPath = realpath($fullPath);
        $baseDir  = realpath(storage_path('app/uploads'));
        abort_if($realPath === false || !str_starts_with($realPath, $baseDir), 403);

        return response()->file($realPath);
    }
}
