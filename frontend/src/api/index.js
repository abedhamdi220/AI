import axios from 'axios';

// تحديد مسار السيرفر الأساسي (Laravel يضيف كلمة api تلقائياً)
const baseURL = process.env.REACT_APP_API_URL || 'https://styloraify.com/api';

const api = axios.create({
    baseURL: baseURL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json', // مهم جداً ليقوم Laravel بإرجاع JSON في حالة الأخطاء وعدم توجيهك لصفحة ويب
    },
});

// إضافة التوكن (Token) تلقائياً لأي طلب يخرج من React
api.interceptors.request.use(
    (config) => {
        // افترض أنك تحفظ التوكن في LocalStorage
        const token = localStorage.getItem('token'); 
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// التعامل مع أخطاء الاستجابة (مثل انتهاء صلاحية التوكن)
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            // التوكن انتهى أو غير صالح -> قم بتسجيل خروج المستخدم
            localStorage.removeItem('token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;