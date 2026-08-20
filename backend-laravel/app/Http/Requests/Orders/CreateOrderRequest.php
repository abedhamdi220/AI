<?php

namespace App\Http\Requests\Orders;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'detail' => $validator->errors()->first(),
        ], 422));
    }

    public function rules(): array
    {
        return [
            'design_image_base64' => ['required_without_all:design_id,showcase_design_id', 'nullable', 'string', 'max:10000000'],
            'prompt'              => ['required_without:showcase_design_id', 'nullable', 'string'],
            'phone_number'        => ['required', 'string'],
            // كانت nullable هنا رغم إن الكنترولر يتطلبها فعلياً (required) — تم توحيدها
            'size'                => ['required', 'string'],
            // تم تغيير string إلى uuid بناءً على بنية قاعدة البيانات لديك
            'design_id'           => ['nullable', 'uuid'],
            // معرّف تصميم جاهز من المعرض عند طلب نفس التصميم مباشرة
            'showcase_design_id'  => ['nullable', 'string'],
            'color'               => ['nullable', 'string'],
            // كانت مفقودة من هذا الملف رغم استخدامها في OrderController
            'clothing_type'       => ['nullable', 'string'],
            'coupon_code'         => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'يرجى إدخال جميع البيانات المطلوبة',
        ];
    }
}
