# سجل التحقق للنسخة المدمجة

## الملخص

تم التحقق من الملفات المرفقة قبل دمجها، وفحص مسارات الأرشيفات لمنع اجتياز المجلدات، ومراجعة التداخل مع هيكل المستودع الحالي. دُمجت ملفات Laravel في `backend-laravel/`، وملفات React في `frontend/`، وملف HTML العام في `frontend/public/`، وخدمة Python في `backend-laravel/image-ai-service/`.

| الفحص | النتيجة | الدليل |
|---|---|---|
| سلامة الأرشيفات | ناجح | لم تظهر مسارات مطلقة أو مكونات `..` عند فحص الأرشيفات الخمسة. |
| دمج المسارات | ناجح | بقي `app/routes/database` ضمن Laravel، و`src/public/index.html` ضمن React، وخدمة الصور ضمن مسارها الخاص. |
| أسماء Form Requests | صُححت | تم تطبيع ثلاثة أسماء ملفات لتتوافق مع أصناف `UpdateOrderStatusRequest` و`UpdateUserLimitRequest` و`Web\LoginRequest` وفق PSR-4. |
| اعتماد Google OAuth | أضيف | أُضيف `@react-oauth/google` إلى `frontend/package.json` وملف القفل. |
| بناء React | ناجح | مر `npm run build` في `frontend/` وأنتج bundle إنتاجيًا. |
| React test runner | يعمل دون اختبارات مطابقة | أمر الاختبار لا يجد ملفات `*.test` أو `*.spec` في الواجهة حاليًا؛ مر فقط مع `--passWithNoTests`. |
| صياغة خدمة Python | ناجح | تم فحص `image_generator.py` عبر compile-only دون بدء الخادم أو استدعاء مزود خارجي. |
| Laravel/PHPUnit | غير منفذ | لم يتوفر PHP أو Composer في بيئة الفحص، رغم وجود `artisan` وlockfile وملف PHPUnit في المستودع. |
| موفر الصور الحقيقي | غير منفذ | يتطلب بيانات اعتماد وحزمة `emergentintegrations` متوافقة في بيئة آمنة. |
| Google OAuth الحقيقي | غير منفذ | يتطلب client ID ونطاقات redirect وبيئة Laravel متصلة. |

## ملاحظات الدمج

الملف `public/index.html` المرفق هو نقطة دخول Create React App، ولذلك وضع في `frontend/public/` ولم يستبدل `backend-laravel/public/index.php`. بهذه الطريقة يبقى Laravel مسؤولًا عن API ويستمر React كواجهة مستقلة. كما استُبعدت لقطة `database.sqlite` من الأرشيف؛ لم تحتوِ على بيانات مستخدم بحسب فحص العدادات، لكنها حالة محلية مولدة وليست مصدر مخطط قاعدة البيانات.

| الملف أو الفئة | الإجراء | سبب القرار |
|---|---|---|
| `app/` و`routes/` | دمج في `backend-laravel/` | تحتوي متحكمات وRequests وResources وأحداثًا ومستمعات ضمن namespace Laravel. |
| `database/seeders/` | دمج فقط | migrations الموجودة أصلًا هي مصدر المخطط؛ ملف SQLite لا يرفع إلى Git. |
| `src/` | دمج في `frontend/src/` | تحديث واجهة التصميم والإدارة والمصادقة. |
| `public/index.html` | دمج في `frontend/public/` | قالب HTML لواجهة React وليس Laravel public entrypoint. |
| `image_generator.py` و`requirements.txt` | دمج في `image-ai-service/` | يمثلان خدمة FastAPI الداخلية وتبعياتها العامة. |

## متطلبات قبول قبل الإنتاج

يجب تنفيذ الاختبارات التالية في بيئة توفر PHP وComposer وقاعدة اختبار ومزود صورة اختبار قبل وصف هذه النسخة بأنها جاهزة للإنتاج. لا تستبدل هذه الاختبارات بفحص build أو فحص صياغة فقط.

| الاختبار المطلوب | معيار القبول |
|---|---|
| Composer/PHPUnit | تثبيت من `composer.lock` وتشغيل PHPUnit بلا أخطاء تحميل أو مسارات. |
| migrations/seeders | إنشاء schema فارغ وتشغيل seeders دون إدخال بيانات إنتاجية أو مخالفات مفاتيح أجنبية. |
| صلاحيات المستخدم | مستخدم لا يستطيع الوصول إلى تصميم أو طلب أو لوحة مدير لا يملكها. |
| حصة التصاميم | لا تتجاوز الحصة عند الطلبات المتزامنة أو فشل خدمة الصور. |
| FastAPI الداخلي | رفض الطلب ذي `X-Internal-Key` غير الصحيح عند تفعيل السر، والسماح للطلب الصحيح فقط. |
| توليد صورة | نتيجة صورة حقيقية مع حالة صريحة للشعار والصورة الشخصية والتحذيرات. |
| Google OAuth | تسجيل دخول كامل ضمن origin وredirect URL معتمدين، دون إظهار token في سجل المتصفح. |
| CORS وHTTPS | الواجهة المسموحة فقط تستطيع الاتصال، وجميع endpoints العلنية تستخدم HTTPS. |

## تنبيه الاعتماديات

أظهر تثبيت اعتماديات الواجهة تحذيرات peer dependencies وحزمًا قديمة داخل منظومة Create React App الحالية، كما أظهر تقرير npm وجود تنبيهات أمنية. لم تنفذ ترقيات جماعية أو `npm audit fix --force` لأن ذلك قد يغير React/CRACO وواجهات المكونات بصورة كاسرة. ينبغي معالجة التحديثات الأمنية في فرع صيانة منفصل مع اختبار شامل، لا ضمن دمج وظيفي محدود.
