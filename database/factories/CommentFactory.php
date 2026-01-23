<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create('ar_SA');

        $subjects = [
            'متابعة العميل', 'استفسار عن الخدمة', 'تحديد موعد اجتماع', 
            'ملاحظات المكالمة الهاتفية', 'طلب تعديل في العرض', 'شكوى بخصوص الفاتورة',
            'تحديث بيانات العميل', 'مناقشة العقد', 'تم إرسال عرض السعر'
        ];

        $comments = [
            'تم الاتصال بالعميل ولم يرد، سيتم المحاولة لاحقاً.',
            'العميل مهتم جداً بالخدمة الاستشارية ويرغب في اجتماع يوم الخميس.',
            'تم إرسال عرض السعر المبدئي وجاري انتظار الموافقة.',
            'العميل يشتكي من تأخر الرد، تم الاعتذار وتصعيد التذكرة.',
            'طلب العميل تغيير موعد الاجتماع القادم لظروف طارئة.',
            'تم الاتفاق على كافة التفاصيل، بانتظار توقيع العقد.',
            'العميل يرغب في إضافة خدمات جديدة للباقة الحالية.',
            'ملاحظة: العميل يفضل التواصل عبر الواتساب فقط.',
            'تم تحديث بيانات العميل وإضافة العنوان الجديد.',
            'العميل غير راضٍ عن السعر المقدم ويطلب خصم إضافي.'
        ];

        return [
            'content' => $faker->randomElement($comments), 
            'subject' => $faker->randomElement($subjects),
            'outcome' => $faker->randomElement(['positive', 'neutral', 'negative']),
            'type_id' => null, // Override in seeder
            // 'client_id' and 'user_id' should be provided
        ];
    }
}
