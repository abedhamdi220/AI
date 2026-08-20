import React, { useState } from 'react';
import axios from 'axios';
import { GoogleOAuthProvider, GoogleLogin } from '@react-oauth/google';
import { toast } from 'sonner';

const GOOGLE_CLIENT_ID = process.env.REACT_APP_GOOGLE_CLIENT_ID;
const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;

export default function GoogleLoginButton({ onLoginSuccess, text = "continue_with" }) {
  const [loading, setLoading] = useState(false);

  const handleGoogleSuccess = async (credentialResponse) => {
    setLoading(true);
    try {
      const googleToken = credentialResponse.credential;

      const response = await axios.post(`${BACKEND_URL}/api/oauth/google`, {
        credential: googleToken
      });

      const { access_token, user } = response.data;

      // حفظ التوكن في التخزين المحلي
      localStorage.setItem('token', access_token);

      toast.success('تم تسجيل الدخول بنجاح!');

      if (onLoginSuccess) {
        onLoginSuccess(access_token, user);
      }

    } catch (error) {
      console.error('Google login error:', error);
      const errorMsg = error.response?.data?.detail || 'حدث خطأ أثناء تسجيل الدخول بواسطة جوجل';
      toast.error(errorMsg);
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleError = () => {
    toast.error('فشل الاتصال بخدمة جوجل، يرجى المحاولة مرة أخرى.');
  };

  return (
    <GoogleOAuthProvider clientId={GOOGLE_CLIENT_ID}>
      <div className={`w-full flex justify-center ${loading ? 'opacity-50 pointer-events-none' : ''}`}>
        <GoogleLogin
          onSuccess={handleGoogleSuccess}
          onError={handleGoogleError}
          useOneTap={false}
          theme="outline"
          text={text} // استخدام النص الممرر (تسجيل دخول أو إنشاء حساب)
          shape="rectangular"
          width="100%"
          locale="ar" // إجبار عرض زر جوجل باللغة العربية
        />
      </div>
    </GoogleOAuthProvider>
  );
}