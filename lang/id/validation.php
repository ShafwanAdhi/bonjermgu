<?php

/*
 * Indonesian validation messages. Only the rules this application actually
 * uses are translated — add an entry when you introduce a new rule rather
 * than importing a full translation bundle.
 *
 * Interface text is Indonesian; identifiers stay English (AD-16).
 */

return [

    'accepted' => ':attribute harus disetujui.',
    'after' => ':attribute harus tanggal setelah :date.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'before' => ':attribute harus tanggal sebelum :date.',
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':attribute bukan tanggal yang sah.',
    'date_format' => ':attribute tidak cocok dengan format :format.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus terdiri dari :digits angka.',
    'digits_between' => ':attribute harus terdiri dari :min sampai :max angka.',
    'email' => ':attribute harus berupa alamat email yang sah.',
    'exists' => ':attribute yang dipilih tidak sah.',
    'in' => ':attribute yang dipilih tidak sah.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'max' => [
        'array' => ':attribute tidak boleh lebih dari :max item.',
        'file' => ':attribute tidak boleh lebih dari :max kilobita.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => ':attribute harus memiliki minimal :min item.',
        'file' => ':attribute harus minimal :min kilobita.',
        'numeric' => ':attribute harus minimal :min.',
        'string' => ':attribute harus minimal :min karakter.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'regex' => 'Format :attribute tidak sah.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi bila :other adalah :value.',
    'same' => ':attribute dan :other harus sama.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',

    'custom' => [],

    'attributes' => [],

];
