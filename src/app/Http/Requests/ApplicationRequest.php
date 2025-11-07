<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 出勤・退勤
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i'],

            // 休憩（配列）
            'rest_start_time'   => ['array'],
            'rest_start_time.*' => ['required', 'date_format:H:i'],
            'rest_end_time'     => ['array'],
            'rest_end_time.*'   => ['required', 'date_format:H:i'],

            // 新規休憩（未入力OK）
            'new_rest_start_time' => ['nullable', 'date_format:H:i'],
            'new_rest_end_time'   => ['nullable', 'date_format:H:i'],

            // 備考
            'notes' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            // 出勤・退勤
            'start_time.required' => '出勤時間は必須です。',
            'start_time.date_format' => '出勤時間は時間形式で入力してください。',
            'end_time.required' => '退勤時間は必須です。',
            'end_time.date_format' => '退勤時間は時間形式で入力してください。',

            // 休憩（配列）
            'rest_start_time.required' => '休憩開始時間は必須です。',
            'rest_start_time.*.required' => '休憩開始時間は必須です。',
            'rest_start_time.*.date_format' => '休憩開始時間は時間形式で入力してください。',
            'rest_end_time.required' => '休憩終了時間は必須です。',
            'rest_end_time.*.required' => '休憩終了時間は必須です。',
            'rest_end_time.*.date_format' => '休憩終了時間は時間形式で入力してください.',

            // 備考
            'notes.required' => '備考を記入してください。',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $start = $this->start_time;
            $end   = $this->end_time;

            // ===== 出勤・退勤 =====
            if ($start > $end) {
                $validator->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // ===== 休憩(既存休憩) =====
            $restStarts = $this->input('rest_start_time', []);
            $restEnds   = $this->input('rest_end_time', []);

            foreach ($restStarts as $i => $restStart) {
                $restEnd = $restEnds[$i] ?? null;

                if ($restStart && $restEnd && ($restEnd < $restStart)) {
                    $validator->errors()->add("rest_start_time.$i", '休憩開始時間もしくは休憩終了時間が不適切な値です。');
                }

                if ($restStart < $start || $restEnd > $end) {
                    $validator->errors()->add("rest_start_time.$i", '休憩時間が不適切な値です');
                }

                if ($restEnd > $end) {
                    $validator->errors()->add("rest_start_time.$i", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }

            // ===== 休憩（追加分） =====
            $newStart = $this->input('new_rest_start_time');
            $newEnd   = $this->input('new_rest_end_time');

            if ($newStart || $newEnd) {
                if ($newStart && $newEnd && ($newEnd < $newStart)) {
                    $validator->errors()->add('new_rest_start_time', '休憩開始時間もしくは休憩終了時間が不適切な値です。');
                }

                if ($newStart < $start || $newEnd > $end) {
                    $validator->errors()->add('new_rest_start_time', '休憩時間が不適切な値です');
                }

                if ($newEnd > $end) {
                    $validator->errors()->add('new_rest_end_time', '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}
