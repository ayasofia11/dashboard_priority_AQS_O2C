<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La vraie protection par rôle (Gate 'import-orders') est sur la ROUTE, pas ici.
        // Cette classe ne valide QUE la forme du fichier envoyé, pas qui a le droit de l'envoyer.
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Merci de sélectionner un fichier.',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx ou .xls).',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}
