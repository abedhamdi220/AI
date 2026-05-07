<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // إشعارات قاعدة البيانات في لارافل تخزن البيانات في حقل 'data' كـ JSON
        $data = $this->data;

        return [
            'id'         => $this->id,
            'title'      => $data['title'] ?? 'إشعار جديد',
            'message'    => $data['message'] ?? '',
            'type'       => $data['type'] ?? 'info', // مثلاً: success, error, info
            'action_url' => $data['action_url'] ?? null, // رابط للانتقال إليه عند الضغط
            'is_read'    => !is_null($this->read_at),
            'read_at'    => $this->read_at ? $this->read_at->toIso8601String() : null,
            'created_at' => $this->created_at->toIso8601String(),
            // حقل إضافي للوقت المقروء بشرياً (مثل: منذ دقيقتين)
            'time_ago'   => $this->created_at->diffForHumans(),
        ];
    }
}
