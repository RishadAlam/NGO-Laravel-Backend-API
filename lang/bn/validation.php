<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute ক্ষেত্রটি অবশ্যই গ্রহণ করতে হবে।',
    'accepted_if' => ':attribute ক্ষেত্রটি অবশ্যই গ্রহণ করা উচিত যখন :other = :value',
    'active_url' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ URL হতে হবে।',
    'after' => ':attribute ক্ষেত্রটি অবশ্যই :date এর পরে একটি তারিখ হতে হবে।',
    'after_or_equal' => ':attribute ক্ষেত্রটি অবশ্যই :date এর পরে বা তার সমান হতে হবে।',
    'alpha' => ':attribute ক্ষেত্রে শুধুমাত্র অক্ষর থাকতে হবে।',
    'alpha_dash' => ':attribute ক্ষেত্রে শুধুমাত্র অক্ষর, সংখ্যা, ড্যাশ এবং আন্ডারস্কোর থাকতে হবে।',
    'alpha_num' => ':attribute ক্ষেত্রে শুধুমাত্র অক্ষর এবং সংখ্যা থাকতে হবে।',
    'array' => ':attribute ক্ষেত্র অবশ্যই একটি অ্যারে হতে হবে।',
    'ascii' => ':attribute ক্ষেত্রে শুধুমাত্র একক-বাইট আলফানিউমেরিক অক্ষর এবং প্রতীক থাকতে হবে।',
    'before' => ':attribute ক্ষেত্রের আগে একটি তারিখ হতে হবে :date.',
    'before_or_equal' => ':attribute ক্ষেত্র অবশ্যই :date এর আগে বা সমান একটি তারিখ হতে হবে।',
    'between' => [
        'array' => ':attribute ক্ষেত্রের মধ্যে থাকতে হবে :min এবং :max আইটেম।',
        'file' => ':attribute ক্ষেত্রটি অবশ্যই :min এবং :max কিলোবাইটের মধ্যে হতে হবে৷',
        'numeric' => ':attribute ক্ষেত্রটি অবশ্যই :min এবং :max এর মধ্যে হতে হবে৷',
        'string' => ':attribute ক্ষেত্র অবশ্যই :min এবং :max অক্ষরের মধ্যে হতে হবে।',
    ],
    'boolean' => ':attribute ক্ষেত্র সত্য বা মিথ্যা হতে হবে।',
    'can' => ':attribute ক্ষেত্রের একটি অননুমোদিত মান রয়েছে।',
    'confirmed' => ':attribute ক্ষেত্র নিশ্চিতকরণ মেলে না।',
    'current_password' => 'পাসওয়ার্ডটি ভূল।',
    'date' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ তারিখ হতে হবে।',
    'phone' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ ফোন নাম্বার হতে হবে।',
    'date_equals' => ':attribute ক্ষেত্র অবশ্যই একটি তারিখ হতে হবে :date এর সমান।',
    'date_format' => ':attribute ক্ষেত্রটি অবশ্যই :format এর সাথে মিলতে হবে।',
    'decimal' => ':attribute ক্ষেত্রে অবশ্যই :decimal দশমিক স্থান থাকতে হবে।',
    'declined' => ':attribute ক্ষেত্র প্রত্যাখ্যান করা আবশ্যক.',
    'declined_if' => ':attribute ক্ষেত্রটি অবশ্যই প্রত্যাখ্যান করতে হবে যখন :other = :value',
    'different' => ':attribute ক্ষেত্র এবং :other অবশ্যই আলাদা হতে হবে।',
    'digits' => ':attribute ক্ষেত্রটি হতে হবে :digits সংখ্যার।',
    'digits_between' => ':attribute ক্ষেত্রটি অবশ্যই :min এবং :max সংখ্যার মধ্যে হতে হবে৷',
    'dimensions' => ':attribute ক্ষেত্রের অবৈধ ইমেজ মাত্রা আছে৷',
    'distinct' => ':attribute ক্ষেত্রের একটি ডুপ্লিকেট মান আছে।',
    'doesnt_end_with' => ':attribute ক্ষেত্রটি অবশ্যই নিম্নলিখিতগুলির একটি দিয়ে শেষ হবে না: :values.',
    'doesnt_start_with' => ':attribute ক্ষেত্রটি অবশ্যই নিম্নলিখিতগুলির একটি দিয়ে শুরু হবে না: :values.',
    'email' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ ইমেল ঠিকানা হতে হবে।',
    'ends_with' => ':attribute ক্ষেত্রটি অবশ্যই নিম্নলিখিতগুলির একটি দিয়ে শেষ করতে হবে: :values.',
    'enum' => 'নির্বাচিত :attribute অবৈধ।',
    'exists' => 'নির্বাচিত :attribute অবৈধ।',
    'file' => ':attribute ক্ষেত্র একটি ফাইল হতে হবে।',
    'filled' => ':attribute ক্ষেত্রের একটি মান থাকতে হবে।',
    'gt' => [
        'array' => ':attribute ক্ষেত্রে অবশ্যই :value আইটেম এর চেয়ে বেশি থাকতে হবে।',
        'file' => ':attribute ক্ষেত্রটি অবশ্যই :value কিলোবাইটের থেকে বড় হতে হবে।',
        'numeric' => ':attribute ক্ষেত্র অবশ্যই :value এর থেকে বড় হতে হবে।',
        'string' => ':attribute ক্ষেত্র অবশ্যই :value অক্ষরের চেয়ে বড় হতে হবে।',
    ],
    'gte' => [
        'array' => ':attribute ক্ষেত্রে অবশ্যই থাকতে হবে :value আইটেম বা আরও বেশি।',
        'file' => ':attribute ক্ষেত্রটি অবশ্যই :value কিলোবাইটের থেকে বড় বা সমান হতে হবে।',
        'numeric' => ':attribute ক্ষেত্র অবশ্যই :value এর থেকে বড় বা সমান হতে হবে।',
        'string' => ':attribute ক্ষেত্র অবশ্যই :value অক্ষরের চেয়ে বড় বা সমান হতে হবে।',
    ],
    'image' => ':attribute ক্ষেত্রটি একটি চিত্র হতে হবে.',
    'in' => 'নির্বাচিত :attribute অবৈধ।',
    'in_array' => ':attribute ক্ষেত্রটি অবশ্যই :other মধ্যে বিদ্যমান থাকতে হবে।',
    'integer' => ':attribute ক্ষেত্র একটি পূর্ণসংখ্যা হতে হবে।',
    'ip' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ IP ঠিকানা হতে হবে।',
    'ipv4' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ IPv4 ঠিকানা হতে হবে।',
    'ipv6' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ IPv6 ঠিকানা হতে হবে।.',
    'json' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ JSON স্ট্রিং হতে হবে।',
    'lowercase' => ':attribute ক্ষেত্র ছোট হাতের হতে হবে।',
    'lt' => [
        'array' => ':attribute ক্ষেত্রে :value থেকে কম আইটেম থাকতে হবে।',
        'file' => ':attribute ক্ষেত্রটি অবশ্যই :value কিলোবাইটের চেয়ে কম হতে হবে।',
        'numeric' => ':attribute ক্ষেত্র অবশ্যই :value এর চেয়ে কম হতে হবে।',
        'string' => ':attribute ক্ষেত্র অবশ্যই :value অক্ষরের চেয়ে কম হতে হবে।',
    ],
    'lte' => [
        'array' => ':attribute ক্ষেত্রে অবশ্যই :value আইটেম এর বেশি থাকবে না।',
        'file' => ':attribute ক্ষেত্রটি অবশ্যই :value কিলোবাইটের থেকে কম বা সমান হতে হবে।',
        'numeric' => ':attribute ক্ষেত্র অবশ্যই :value এর থেকে কম বা সমান হতে হবে।',
        'string' => ':attribute ক্ষেত্র অবশ্যই :value অক্ষরের চেয়ে কম বা সমান হতে হবে।',
    ],
    'mac_address' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ MAC ঠিকানা হতে হবে।',
    'max' => [
        'array' => ':attribute ক্ষেত্র অবশ্যই :max আইটেম এর বেশি থাকবে না।',
        'file' => ':attribute ক্ষেত্রটি অবশ্যই :max কিলোবাইটের বেশি হওয়া উচিত নয়৷',
        'numeric' => ':attribute ক্ষেত্র অবশ্যই :max এর চেয়ে বেশি হওয়া উচিত নয়।',
        'string' => ':attribute ক্ষেত্র অবশ্যই :max অক্ষরের চেয়ে বেশি হওয়া উচিত নয়।',
    ],
    'max_digits' => ':attribute ফিল্ডে অবশ্যই :max সংখ্যার বেশি হওয়া উচিত নয়।',
    'mimes' => ':attribute ক্ষেত্রটি :values টাইপের একটি ফাইল হতে হবে।',
    'mimetypes' => ':attribute ক্ষেত্রটি :values টাইপের একটি ফাইল হতে হবে।',
    'min' => [
        'array' => ':attribute ক্ষেত্রে অন্তত :min আইটেম থাকতে হবে।',
        'file' => ':attribute ক্ষেত্রটি কমপক্ষে :min কিলোবাইট হতে হবে।',
        'numeric' => ':attribute ক্ষেত্র অন্তত :min হতে হবে।',
        'string' => ':attribute ক্ষেত্র অবশ্যই কমপক্ষে :min অক্ষর হতে হবে।',
    ],
    'min_digits' => ':attribute ক্ষেত্রটি কমপক্ষে :min সংখ্যার থাকতে হবে।',
    'missing' => ':attribute ক্ষেত্র অনুপস্থিত হতে হবে।',
    'missing_if' => ':attribute ক্ষেত্রটি অনুপস্থিত থাকা আবশ্যক যখন :other সমান :value.',
    'missing_unless' => ':attribute ক্ষেত্র অনুপস্থিত হতে হবে যদি না :other সমান :value.',
    'missing_with' => ':attribute ক্ষেত্র অনুপস্থিত থাকা আবশ্যক যখন :values উপস্থিত থাকে।',
    'missing_with_all' => ':attribute ক্ষেত্র অনুপস্থিত থাকা আবশ্যক যখন :values উপস্থিত থাকে।',
    'multiple_of' => ':attribute ক্ষেত্র অবশ্যই :value এর একাধিক হতে হবে।',
    'not_in' => 'নির্বাচিত :attribute অবৈধ।',
    'not_regex' => ':attribute ক্ষেত্রের বিন্যাস অবৈধ।',
    'numeric' => ':attribute ক্ষেত্র একটি সংখ্যা হতে হবে।',
    'password' => [
        'letters' => ':attribute ক্ষেত্রে অন্তত একটি অক্ষর থাকতে হবে।',
        'mixed' => ':attribute ক্ষেত্রে অন্তত একটি বড় হাতের এবং একটি ছোট হাতের অক্ষর থাকতে হবে।',
        'numbers' => ':attribute ক্ষেত্রে অন্তত একটি সংখ্যা থাকতে হবে।',
        'symbols' => ':attribute ক্ষেত্রে অন্তত একটি প্রতীক থাকতে হবে।',
        'uncompromised' => ':attribute একটি ডেটা লিকের মধ্যে উপস্থিত হয়েছে। অনুগ্রহ করে একটি ভিন্ন চয়ন করুন :attribute.',
    ],
    'present' => ':attribute ক্ষেত্র উপস্থিত থাকতে হবে।',
    'prohibited' => ':attribute ক্ষেত্র নিষিদ্ধ।',
    'prohibited_if' => ':attribute ক্ষেত্র নিষিদ্ধ যখন :other সমান :value.',
    'prohibited_unless' => ':attribute ক্ষেত্র নিষিদ্ধ যদি না :values এর মদ্ধে :other থাকে।',
    'prohibits' => ':attribute ক্ষেত্র নিষেধ করে :other উপস্থিত হতে।',
    'regex' => ':attribute ক্ষেত্রের বিন্যাস অবৈধ।',
    'required' => ':attribute ক্ষেত্রটি প্রয়োজন।',
    'required_array_keys' => ':attribute ক্ষেত্রের জন্য এন্ট্রি থাকতে হবে: :values.',
    'required_if' => ':attribute ক্ষেত্রের প্রয়োজন হয় যখন :other সমান :value.',
    'required_if_accepted' => ':attribute ক্ষেত্রের প্রয়োজন হয় যখন :other গৃহীত হয়।',
    'required_unless' => ':attribute ক্ষেত্র আবশ্যক যদি না :values এর মদ্ধে :other থাকে।',
    'required_with' => ':attribute ষেত্রের প্রয়োজন হয় যখন :values উপস্থিত থাকে।',
    'required_with_all' => ':attribute ষেত্রের প্রয়োজন হয় যখন :values উপস্থিত থাকে।',
    'required_without' => ':attribute ষেত্রের প্রয়োজন হয় যখন :values উপস্থিত নয়।',
    'required_without_all' => ':attribute ক্ষেত্রের প্রয়োজন হয় যখন :values গুলির মধ্যে কোনোটিই উপস্থিত থাকে না।',
    'same' => ':attribute ক্ষেত্র অবশ্যই :other এর সাথে মিল থাকতে হবে।',
    'size' => [
        'array' => ':attribute ক্ষেত্রে অবশ্যই :size টি আইটেম থকতে হবে।',
        'file' => ':attribute ক্ষেত্র অবশ্যই  :size কিলোবাইটের হতে হবে।',
        'numeric' => ':attribute ক্ষেত্র অবশ্যই :size হতে হবে।',
        'string' => ':attribute ক্ষেত্র অবশ্যই :size অক্ষরের হতে হবে।',
    ],
    'starts_with' => ':attribute ক্ষেত্রটি অবশ্যই নিম্নলিখিতগুলির একটি দিয়ে শুরু করতে হবে: :values.',
    'string' => ':attribute ক্ষেত্র একটি স্ট্রিং হতে হবে।',
    'timezone' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ টাইমজোন হতে হবে।',
    'unique' => ':attribute আগেই নেয়া হয়েছে.',
    'uploaded' => ':attribute আপলোড করতে ব্যর্থ।',
    'uppercase' => ':attribute ক্ষেত্র অবশ্যই বড় হাতের হতে হবে।',
    'url' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ URL হতে হবে।',
    'ulid' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ ULID হতে হবে।',
    'uuid' => ':attribute ক্ষেত্রটি অবশ্যই একটি বৈধ UUID হতে হবে।',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
