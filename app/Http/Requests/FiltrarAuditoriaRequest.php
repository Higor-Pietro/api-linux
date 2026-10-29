<?php

namespace App\Http\Requests;

use App\Enums\AcaoAuditoria;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

class FiltrarAuditoriaRequest extends FormRequest
{
    /**
     * Só quem pode ver os logs pode filtrá-los.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Activity::class);
    }

    /**
     * Todos os filtros são opcionais.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'usuario_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'acao' => ['nullable', Rule::enum(AcaoAuditoria::class)],
            'data_inicio' => ['nullable', 'date','before_or_equal:today'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'data_fim.after_or_equal' => __('A data final não pode ser anterior à data inicial.'),
        ];
    }
}