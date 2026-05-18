import { useState } from "react";
import axios from "axios";
import { toast } from "sonner";
import { Sparkles, Wand2, Heart, TrendingUp } from "lucide-react";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "../components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../components/ui/tabs";
import WhatsAppButton from "../components/WhatsAppButton";
import { GoogleOAuthProvider, useGoogleLogin } from '@react-oauth/google';

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const GOOGLE_CLIENT_ID = process.env.REACT_APP_GOOGLE_CLIENT_ID;
const API = `${BACKEND_URL}/api`;

function LandingPageContent({ onLogin }) {
  const [showAuth, setShowAuth] = useState(false);
  const [authMode, setAuthMode] = useState("login");
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    username: "",
    email: "",
    password: ""
  });

  const handleInputChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const validateEmail = (email) => {
    // يجب أن يكون البريد Gmail فقط
    const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
    return gmailRegex.test(email);
  };

  const validatePassword = (password) => {
    // يجب أن تحتوي على أحرف وأرقام (6 أحرف على الأقل)
    const hasLetters = /[a-zA-Z]/.test(password);
    const hasNumbers = /[0-9]/.test(password);
    return password.length >= 6 && hasLetters && hasNumbers;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (authMode === "register") {
      if (!validateEmail(formData.email)) {
        toast.error("عذراً، التسجيل متاح فقط باستخدام بريد Gmail");
        return;
      }
      if (!validatePassword(formData.password)) {
        toast.error("كلمة المرور يجب أن تكون 6 أحرف على الأقل وتحتوي على أحرف وأرقام");
        return;
      }
    }

    setLoading(true);
    try {
      if (authMode === "register") {
        // إنشاء الحساب (الباك إند يعيد التوكن مباشرة كما في AuthController)
        const response = await axios.post(`${API}/auth/register`, {
          username: formData.username,
          email: formData.email,
          password: formData.password
        });
        
        toast.success("تم إنشاء الحساب وتسجيل الدخول بنجاح!");
        
        // الاعتماد على التوكن المرتجع مباشرة من دالة الـ Register
        localStorage.setItem("token", response.data.access_token);
        if (onLogin) onLogin(response.data.access_token, response.data.user);
        setShowAuth(false);
        
      } else {
        // تسجيل الدخول العادي بصيغة JSON ليتوافق مع Laravel
        const response = await axios.post(`${API}/auth/login`, {
          username: formData.username, // تم التعديل لتكون username بدلاً من email
          password: formData.password
        });
        
        localStorage.setItem("token", response.data.access_token);
        toast.success("تم تسجيل الدخول بنجاح!");
        if (onLogin) onLogin(response.data.access_token, response.data.user);
        setShowAuth(false);
      }
    } catch (error) {
      toast.error(error.response?.data?.detail || "حدث خطأ، يرجى التأكد من صحة البيانات والمحاولة مجدداً");
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleSuccess = async (tokenResponse) => {
    setLoading(true);
    try {
      const googleToken = tokenResponse.access_token;
      
      const response = await axios.post(`${API}/oauth/google`, {
        credential: googleToken
      });

      const { access_token, user } = response.data;
      
      localStorage.setItem('token', access_token);
      
      toast.success('تم تسجيل الدخول بنجاح!');

      if (onLogin) {
        onLogin(access_token, user);
      }
      setShowAuth(false);

    } catch (error) {
      console.error('Google login error:', error);
      const errorMsg = error.response?.data?.detail || 'حدث خطأ أثناء تسجيل الدخول بواسطة جوجل';
      toast.error(errorMsg);
    } finally {
      setLoading(false);
    }
  };

  const loginWithGoogle = useGoogleLogin({
    onSuccess: handleGoogleSuccess,
    onError: () => toast.error('فشل الاتصال بخدمة جوجل، يرجى المحاولة مرة أخرى.')
  });

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#F5F0E8] via-[#E8DCC8] to-[#F5F0E8] relative overflow-hidden">
      <WhatsAppButton />
      
      <div className="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-[#D4AF37]/20 to-transparent rounded-full blur-3xl"></div>
      <div className="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-[#3E2723]/10 to-transparent rounded-full blur-3xl"></div>

      <div className="relative z-10 container mx-auto px-4 py-16">
        <div className="text-center mb-16 fade-in">
          <div className="flex justify-center mb-6">
            <div className="p-4 bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl">
              <Sparkles className="w-16 h-16 text-[#D4AF37]" />
            </div>
          </div>
          <h1 className="text-5xl sm:text-6xl lg:text-7xl font-bold text-[#3E2723] mb-6 leading-tight">
            استوديو تصميم الأزياء
          </h1>
          <p className="text-lg sm:text-xl text-[#5D4037] max-w-2xl mx-auto mb-8">
            صمم ملابسك الخاصة بلمسة من الذكاء الاصطناعي - حول أفكارك إلى تصاميم احترافية فورية
          </p>
          <Button
            onClick={() => setShowAuth(true)}
            className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#9A7A1A] text-white text-lg px-12 py-6 rounded-xl shadow-2xl hover:shadow-[#D4AF37]/30 transition-all duration-300 hover:scale-105"
          >
            <Wand2 className="ml-2 w-6 h-6" />
            ابدأ التصميم الآن
          </Button>
        </div>

        {/* Features Grid */}
        <div className="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto mt-20">
          <div className="glass rounded-3xl p-8 card-hover">
            <div className="bg-gradient-to-br from-[#D4AF37] to-[#B8941F] w-16 h-16 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
              <Sparkles className="w-8 h-8 text-white" />
            </div>
            <h3 className="text-2xl font-bold text-[#3E2723] mb-4">ذكاء اصطناعي متقدم</h3>
            <p className="text-[#5D4037] leading-relaxed">
              احصل على تصاميم احترافية فورية باستخدام أحدث تقنيات الذكاء الاصطناعي
            </p>
          </div>

          <div className="glass rounded-3xl p-8 card-hover">
            <div className="bg-gradient-to-br from-[#D4AF37] to-[#B8941F] w-16 h-16 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
              <Wand2 className="w-8 h-8 text-white" />
            </div>
            <h3 className="text-2xl font-bold text-[#3E2723] mb-4">تخصيص كامل</h3>
            <p className="text-[#5D4037] leading-relaxed">
              صف تصميمك بكلماتك واحصل على نتائج تطابق رؤيتك الإبداعية
            </p>
          </div>

          <div className="glass rounded-3xl p-8 card-hover">
            <div className="bg-gradient-to-br from-[#D4AF37] to-[#B8941F] w-16 h-16 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
              <Heart className="w-8 h-8 text-white" />
            </div>
            <h3 className="text-2xl font-bold text-[#3E2723] mb-4">حفظ ومشاركة</h3>
            <p className="text-[#5D4037] leading-relaxed">
              احتفظ بتصاميمك المفضلة وشاركها مع الآخرين بكل سهولة
            </p>
          </div>
        </div>

        {/* How It Works Section */}
        <div className="mt-24 max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl sm:text-4xl font-bold text-[#3E2723] mb-4">كيف يعمل؟</h2>
            <p className="text-lg text-[#5D4037]">ثلاث خطوات بسيطة للحصول على تصميمك المثالي</p>
          </div>
          <div className="grid md:grid-cols-3 gap-8">
            <div className="text-center relative">
              <div className="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-[#D4AF37] to-[#B8941F] rounded-full flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                1
              </div>
              <h3 className="text-xl font-bold text-[#3E2723] mb-3">اختر القالب</h3>
              <p className="text-[#5D4037]">اختر من مجموعة متنوعة من القوالب الجاهزة</p>
              <div className="hidden md:block absolute top-10 left-0 w-full h-0.5 bg-gradient-to-l from-[#D4AF37]/50 to-transparent -z-10"></div>
            </div>
            <div className="text-center relative">
              <div className="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-[#D4AF37] to-[#B8941F] rounded-full flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                2
              </div>
              <h3 className="text-xl font-bold text-[#3E2723] mb-3">صف تصميمك</h3>
              <p className="text-[#5D4037]">أكتب وصفاً لتصميمك ودع الذكاء الاصطناعي يبدع</p>
              <div className="hidden md:block absolute top-10 left-0 w-full h-0.5 bg-gradient-to-l from-[#D4AF37]/50 to-transparent -z-10"></div>
            </div>
            <div className="text-center">
              <div className="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-[#D4AF37] to-[#B8941F] rounded-full flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                3
              </div>
              <h3 className="text-xl font-bold text-[#3E2723] mb-3">احفظ وشارك</h3>
              <p className="text-[#5D4037]">احفظ تصميمك واطلب تنفيذه أو شاركه</p>
            </div>
          </div>
        </div>

        {/* Stats Section */}
        <div className="mt-24 glass rounded-3xl p-8 sm:p-12 max-w-4xl mx-auto">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8 text-center">
            <div className="p-4">
              <div className="text-3xl sm:text-5xl font-bold text-[#D4AF37] mb-2">1000+</div>
              <div className="text-sm sm:text-lg text-[#5D4037]">تصميم يومي</div>
            </div>
            <div className="p-4">
              <div className="text-3xl sm:text-5xl font-bold text-[#D4AF37] mb-2">500+</div>
              <div className="text-sm sm:text-lg text-[#5D4037]">مصمم نشط</div>
            </div>
            <div className="p-4">
              <div className="text-3xl sm:text-5xl font-bold text-[#D4AF37] mb-2">98%</div>
              <div className="text-sm sm:text-lg text-[#5D4037]">رضا المستخدمين</div>
            </div>
            <div className="p-4">
              <div className="text-3xl sm:text-5xl font-bold text-[#D4AF37] mb-2">24/7</div>
              <div className="text-sm sm:text-lg text-[#5D4037]">دعم فني</div>
            </div>
          </div>
        </div>

        {/* CTA Section */}
        <div className="mt-24 text-center pb-12">
          <h2 className="text-2xl sm:text-3xl font-bold text-[#3E2723] mb-4">جاهز لبدء التصميم؟</h2>
          <p className="text-lg text-[#5D4037] mb-8">سجل الآن واحصل على 3 تصاميم مجانية</p>
          <Button
            onClick={() => setShowAuth(true)}
            className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#9A7A1A] text-white text-lg px-12 py-6 rounded-xl shadow-2xl hover:shadow-[#D4AF37]/30 transition-all duration-300 hover:scale-105"
          >
            <Sparkles className="ml-2 w-6 h-6" />
            ابدأ مجاناً
          </Button>
        </div>
      </div>

      {/* Auth Dialog */}
      <Dialog open={showAuth} onOpenChange={setShowAuth}>
        <DialogContent className="sm:max-w-md" dir="rtl">
          <DialogHeader>
            <DialogTitle className="text-2xl font-bold text-[#3E2723] text-center">
              {authMode === "login" ? "تسجيل الدخول" : "إنشاء حساب جديد"}
            </DialogTitle>
          </DialogHeader>

          <Tabs value={authMode} onValueChange={setAuthMode} className="w-full mt-4">
            <TabsList className="grid w-full grid-cols-2 mb-6">
              <TabsTrigger value="login">تسجيل الدخول</TabsTrigger>
              <TabsTrigger value="register">حساب جديد</TabsTrigger>
            </TabsList>

            <TabsContent value="login">
              <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                  {/* تم تعديل الحقل ليطلب اسم المستخدم ليتطابق مع الـ Backend */}
                  <label className="block text-sm font-medium text-[#3E2723] mb-2">
                    اسم المستخدم
                  </label>
                  <Input
                    type="text"
                    name="username"
                    value={formData.username}
                    onChange={handleInputChange}
                    required
                    className="w-full"
                    dir="ltr"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-[#3E2723] mb-2">
                    كلمة المرور
                  </label>
                  <Input
                    type="password"
                    name="password"
                    value={formData.password}
                    onChange={handleInputChange}
                    required
                    className="w-full"
                    dir="ltr"
                  />
                </div>
                <Button
                  type="submit"
                  disabled={loading}
                  className="w-full bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#9A7A1A] text-white"
                >
                  {loading ? "جاري التحميل..." : "تسجيل الدخول"}
                </Button>
                
                {/* Divider for Login */}
                <div className="relative my-6">
                  <div className="absolute inset-0 flex items-center">
                    <div className="w-full border-t border-[#3E2723]/20"></div>
                  </div>
                  <div className="relative flex justify-center text-sm">
                    <span className="px-4 bg-white text-[#5D4037]">أو تسجيل الدخول بواسطة</span>
                  </div>
                </div>
                
                {/* Google Sign In Button for Login */}
                <Button
                  type="button"
                  onClick={() => loginWithGoogle()}
                  disabled={loading}
                  variant="outline"
                  className="w-full border-2 border-[#3E2723]/20 hover:border-[#D4AF37] hover:bg-[#D4AF37]/5 flex items-center justify-center transition-all"
                >
                  <svg className="w-5 h-5 ml-2" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H.18v2.84C2.27 21.59 6.8 24 12 24z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H.18C-.06 8.04-.2 9.08-.2 10.15s.14 2.11.38 3.08l5.46-2.14z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 6.8 1 2.27 3.41.18 7.07l5.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                  </svg>
                  <span className="font-medium">المتابعة بواسطة جوجل</span>
                </Button>
              </form>
            </TabsContent>

            <TabsContent value="register">
              <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-[#3E2723] mb-2">
                    اسم المستخدم
                  </label>
                  <Input
                    type="text"
                    name="username"
                    value={formData.username}
                    onChange={handleInputChange}
                    required
                    className="w-full"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-[#3E2723] mb-2">
                    البريد الإلكتروني
                  </label>
                  <Input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleInputChange}
                    required
                    className="w-full"
                    dir="ltr"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-[#3E2723] mb-2">
                    كلمة المرور
                  </label>
                  <Input
                    type="password"
                    name="password"
                    value={formData.password}
                    onChange={handleInputChange}
                    required
                    className="w-full"
                    dir="ltr"
                  />
                </div>
                <Button
                  type="submit"
                  disabled={loading}
                  className="w-full bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#9A7A1A] text-white"
                >
                  {loading ? "جاري التحميل..." : "إنشاء حساب"}
                </Button>
                
                {/* Divider for Register */}
                <div className="relative my-6">
                  <div className="absolute inset-0 flex items-center">
                    <div className="w-full border-t border-[#3E2723]/20"></div>
                  </div>
                  <div className="relative flex justify-center text-sm">
                    <span className="px-4 bg-white text-[#5D4037]">أو التسجيل بواسطة</span>
                  </div>
                </div>
                
                {/* Google Sign In Button for Register */}
                <Button
                  type="button"
                  onClick={() => loginWithGoogle()}
                  disabled={loading}
                  variant="outline"
                  className="w-full border-2 border-[#3E2723]/20 hover:border-[#D4AF37] hover:bg-[#D4AF37]/5 flex items-center justify-center transition-all"
                >
                  <svg className="w-5 h-5 ml-2" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H.18v2.84C2.27 21.59 6.8 24 12 24z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H.18C-.06 8.04-.2 9.08-.2 10.15s.14 2.11.38 3.08l5.46-2.14z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 6.8 1 2.27 3.41.18 7.07l5.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                  </svg>
                  <span className="font-medium">التسجيل بواسطة جوجل</span>
                </Button>
              </form>
            </TabsContent>
          </Tabs>
        </DialogContent>
      </Dialog>
    </div>
  );
}

// تغليف الصفحة بالـ Provider لتمكين تسجيل الدخول بحساب جوجل بشكل سليم
export default function LandingPage(props) {
  return (
    <GoogleOAuthProvider clientId={GOOGLE_CLIENT_ID}>
      <LandingPageContent {...props} />
    </GoogleOAuthProvider>
  );
}