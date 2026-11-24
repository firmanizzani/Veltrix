<?php

namespace App\Http\Requests;

use App\Services\ResourceCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $catalog = app(ResourceCatalog::class);
        if (
            $this->route('resource') === 'users' &&
            $this->route('record') &&
            \App\Models\User::find($this->route('record'))?->isOwner()
        ) {
            return false;
        }

        return $catalog->allowed(
            $this->user(),
            $catalog->definition($this->route('resource'))['write'],
        );
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('resource') === 'users') {
            $this->merge(['email' => strtolower($this->input('email', ''))]);
        }
    }

    public function rules(): array
    {
        $key = $this->route('resource');
        $definition = app(ResourceCatalog::class)->definition($key);
        $rules = collect($definition['fields'])
            ->mapWithKeys(fn($f) => [$f['key'] => $f['rules']])
            ->all();
        $id = $this->route('record');
        foreach (
            match ($key) {
                'branches' => ['code'],
                'users' => ['email'],
                'categories', 'ingredients' => ['name'],
                'products' => ['sku'],
                'employees' => ['code'],
                default => [],
            }
            as $column
        ) {
            $rules[$column] = [
                ...explode('|', $rules[$column]),
                Rule::unique((new ($definition['model'])())->getTable(), $column)->ignore(
                    $id,
                ),
            ];
        }
        if ($key === 'users' && !$id) {
            $rules['password'] = 'required|string|min:12|confirmed';
        }
        if ($key === 'products') {
            $imageUrl = $this->input('image_url');
            if (
                is_string($imageUrl) &&
                preg_match('#\A/images/menu/[a-z0-9-]+\.jpg\z#', $imageUrl) === 1 &&
                is_file(public_path(ltrim($imageUrl, '/')))
            ) {
                $rules['image_url'] = 'nullable|string|max:255';
            }
        }
        if (in_array($key, ['recipes', 'purchases'])) {
            $rules['items.*.ingredient_id'] =
                'required|integer|distinct|exists:ingredients,id';
            $rules['items.*.quantity'] = 'required|numeric|gt:0|max:100000000';
            if ($key === 'purchases') {
                $rules['items.*.unit_price'] = 'required|integer|min:0|max:10000000';
            }
        }

        return $rules;
    }
}
