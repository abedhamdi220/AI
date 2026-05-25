import { useState, useEffect } from "react";
import axios from "axios";
import { toast } from "sonner";
import { 
  Sparkles, Heart, Trash2, LogOut, Loader2, Wand2, Save, 
  Edit, X, Phone, ShoppingCart, Package, Ruler, Eye, 
  TrendingUp, Bell, Moon, Sun, Tag, Truck, ArrowRight 
} from "lucide-react";
import { useTheme } from "../contexts/ThemeContext";
import { Button } from "../components/ui/button";
import { Textarea } from "../components/ui/textarea";
import { Card, CardContent } from "../components/ui/card";
import { 
  AlertDialog, AlertDialogAction, AlertDialogCancel, 
  AlertDialogContent, AlertDialogDescription, AlertDialogFooter, 
  AlertDialogHeader, AlertDialogTitle 
} from "../components/ui/alert-dialog";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "../components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

const VIEW_ANGLES = [
  { value: "front", label: "أمامي", icon: "👔" },
  { value: "side", label: "جانبي", icon: "🔄" },
  { value: "back", label: "خلفي", icon: "🔙" }
];

const SIZES = ["XS", "S", "M", "L", "XL", "XXL"];

const LOGO_POSITIONS = [
  { value: "center", label: "وسط الصدر", icon: "⬤" },
  { value: "left", label: "الصدر الأيسر", icon: "◀" },
  { value: "right", label: "الصدر الأيمن", icon: "▶" },
  { value: "bottom", label: "أسفل الملابس", icon: "▼" }
];

// دالة مساعدة لتهيئة روابط الصور والـ Base64 بشكل آمن وسريع
const formatImageSrc = (imgSource) => {
  if (!imgSource) return "";
  if (imgSource.startsWith("http") || imgSource.startsWith("/")) return imgSource;
  if (imgSource.startsWith("data:")) return imgSource;
  return `data:image/png;base64,${imgSource}`;
};

export default function Dashboard({ user, onLogout }) {
  const { isDark, toggleTheme } = useTheme();
  
  const [designs, setDesigns] = useState([]);
  const [showcaseDesigns, setShowcaseDesigns] = useState([]);
  const [orders, setOrders] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);
  const [generating, setGenerating] = useState(false);
  const [deleteDialog, setDeleteDialog] = useState({ open: false, designId: null });
  const [sizeChart, setSizeChart] = useState({});
  const [activeView, setActiveView] = useState("showcase");
  const [showNotifications, setShowNotifications] = useState(false);
  const [designStep, setDesignStep] = useState("select-type"); // "select-type" or "customize"

  const [clothingTypes, setClothingTypes] = useState([]);
  const [viewAngles, setViewAngles] = useState([]);

  // Design State
  const [prompt, setPrompt] = useState("");
  const [enhancedPrompt, setEnhancedPrompt] = useState("");
  const [logoPreview, setLogoPreview] = useState(null);
  const [userPhotoPreview, setUserPhotoPreview] = useState(null);
  const [enhancing, setEnhancing] = useState(false);
  const [selectedPalette, setSelectedPalette] = useState("classic");
  const [selectedViewAngle, setSelectedViewAngle] = useState("front");
  const [selectedClothingType, setSelectedClothingType] = useState(null);
  const [selectedSize, setSelectedSize] = useState("M");
  
  // Preview State
  const [generatedDesign, setGeneratedDesign] = useState(null);
  const [compositeImage, setCompositeImage] = useState(null);
  const [showComposite, setShowComposite] = useState(false);
  const [selectedLogoPosition, setSelectedLogoPosition] = useState("center");
  
  // Coupon State
  const [availableCoupons, setAvailableCoupons] = useState([]);
  const [couponCode, setCouponCode] = useState("");
  const [appliedCoupon, setAppliedCoupon] = useState(null);
  const [validatingCoupon, setValidatingCoupon] = useState(false);
  
  // Order State
  const [showOrderForm, setShowOrderForm] = useState(false);
  const [phoneNumber, setPhoneNumber] = useState("");
  const [submittingOrder, setSubmittingOrder] = useState(false);
  
  // Measurements Dialog
  const [showMeasurements, setShowMeasurements] = useState(false);
  const [showSizeChart, setShowSizeChart] = useState(false);
  const [measurements, setMeasurements] = useState({
    chest: "",
    waist: "",
    hips: "",
    height: "",
    weight: ""
  });
  const [suggestedSize, setSuggestedSize] = useState("");
  
  // Clothing types with descriptions
  const CLOTHING_TYPES = [
    { 
      value: "tshirt", 
      label: "تيشيرت", 
      emoji: "👕",
      description: "تيشيرت قطني مريح للاستخدام اليومي",
      color: "from-blue-400 to-blue-600",
      active: true
    },
    { 
      value: "hoodie", 
      label: "هودي / كنزة", 
      emoji: "🧥",
      description: "هودي دافئ ومريح للشتاء",
      color: "from-purple-400 to-purple-600",
      active: true
    },
    { 
      value: "pants", 
      label: "بنطلون", 
      emoji: "👖",
      description: "بنطلون مريح بتصميم عصري",
      color: "from-amber-400 to-amber-600",
      active: true
    },
    { 
      value: "shirt", 
      label: "قميص رسمي", 
      emoji: "👔",
      description: "قميص أنيق للمناسبات والعمل",
      color: "from-indigo-400 to-indigo-600",
      active: false
    },
    { 
      value: "dress", 
      label: "فستان", 
      emoji: "👗",
      description: "فستان أنيق للمناسبات الخاصة",
      color: "from-pink-400 to-pink-600",
      active: false
    },
    { 
      value: "jacket", 
      label: "جاكيت", 
      emoji: "🧥",
      description: "جاكيت عصري للإطلالة المميزة",
      color: "from-gray-500 to-gray-700",
      active: false
    },
    { 
      value: "polo", 
      label: "بولو", 
      emoji: "👕",
      description: "قميص بولو كلاسيكي وأنيق",
      color: "from-green-400 to-green-600",
      active: false
    },
    { 
      value: "sweater", 
      label: "سويتر", 
      emoji: "🧶",
      description: "سويتر صوف دافئ للشتاء",
      color: "from-red-400 to-red-600",
      active: false
    }
  ];
  
  // Designs quota state
  const [designsQuota, setDesignsQuota] = useState({
    designs_limit: 10,
    designs_used: 0,
    designs_remaining: 10,
    is_unlimited: false
  });

  useEffect(() => {
    fetchDesigns();
    fetchShowcase();
    fetchSizeChart();
    fetchOrders();
    fetchNotifications();
    fetchCoupons();
    fetchDesignsQuota();
    fetchOptions(); 
  }, []);
  
  const fetchDesignsQuota = async () => {
    try {
      const response = await axios.get(`${API}/user/designs-quota`);
      setDesignsQuota(response.data);
    } catch (error) {
      console.error("Failed to fetch designs quota:", error);
      toast.error(error.response?.data?.detail || "فشل في جلب بيانات باقة التصاميم");
    }
  };

  const fetchOptions = async () => {
    try {
      const typesRes = await axios.get(`${API}/options/clothing-types`);
      setClothingTypes(typesRes.data);
      
      const anglesRes = await axios.get(`${API}/options/view-angles`);
      setViewAngles(anglesRes.data);
    } catch (error) {
      console.error("Failed to fetch options:", error);
    }
  };

  const fetchDesigns = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API}/designs`);
      setDesigns(response.data);
    } catch (error) {
      toast.error(error.response?.data?.detail || "فشل في تحميل التصاميم");
    } finally {
      setLoading(false);
    }
  };

  const fetchShowcase = async () => {
    try {
      const response = await axios.get(`${API}/designs/showcase`);
      setShowcaseDesigns(response.data);
    } catch (error) {
      console.error("Failed to fetch showcase:", error);
    }
  };

  const fetchSizeChart = async () => {
    try {
      const response = await axios.get(`${API}/designs/size-chart`);
      setSizeChart(response.data);
    } catch (error) {
      console.error("Failed to fetch size chart:", error);
    }
  };

  const fetchOrders = async () => {
    try {
      const response = await axios.get(`${API}/orders/my-orders`);
      setOrders(response.data);
    } catch (error) {
      console.error("Failed to fetch orders:", error);
      toast.error(error.response?.data?.detail || "فشل في تحميل الطلبات");
    }
  };

  const fetchNotifications = async () => {
    try {
      const response = await axios.get(`${API}/notifications`);
      setNotifications(response.data);
      
      // Get unread count
      const unreadResponse = await axios.get(`${API}/notifications/unread-count`);
      setUnreadCount(unreadResponse.data.count || 0);
    } catch (error) {
      console.error("Failed to fetch notifications:", error);
    }
  };

  const fetchCoupons = async () => {
    try {
      const response = await axios.get(`${API}/coupons`);
      setAvailableCoupons(response.data);
    } catch (error) {
      console.error("Failed to fetch coupons:", error);
    }
  };

  const markNotificationAsRead = async (notificationId) => {
    try {
      await axios.put(`${API}/notifications/${notificationId}/read`);
      setNotifications(notifications.map(n => 
        n.id === notificationId ? { ...n, is_read: true } : n
      ));
      setUnreadCount(Math.max(0, unreadCount - 1));
    } catch (error) {
      console.error("Failed to mark notification as read:", error);
    }
  };

  const markAllNotificationsAsRead = async () => {
    try {
      await axios.put(`${API}/notifications/mark-all-read`);
      setNotifications(notifications.map(n => ({ ...n, is_read: true })));
      setUnreadCount(0);
      toast.success("تم تحديد جميع الإشعارات كمقروءة");
    } catch (error) {
      toast.error(error.response?.data?.detail || "فشل في تحديث الإشعارات");
    }
  };

  const validateCouponCode = async () => {
    if (!couponCode.trim()) {
      toast.error("الرجاء إدخال كود الكوبون");
      return;
    }

    setValidatingCoupon(true);
    try {
      const response = await axios.post(`${API}/coupons/validate`, {
        code: couponCode
      });

      if (response.data.valid) {
        setAppliedCoupon(response.data);
        toast.success(`🎉 تم تطبيق الكوبون! خصم ${response.data.discount_percentage}%`);
      } else {
        toast.error(response.data.message || "الكوبون غير صالح");
        setAppliedCoupon(null);
      }
    } catch (error) {
      toast.error(error.response?.data?.detail || "كود الكوبون غير صحيح");
      setAppliedCoupon(null);
    } finally {
      setValidatingCoupon(false);
    }
  };

  const removeCoupon = () => {
    setCouponCode("");
    setAppliedCoupon(null);
    toast.info("تم إزالة الكوبون");
  };

  const saveMeasurements = async () => {
    try {
      const response = await axios.put(`${API}/user/measurements`, {
        chest: parseFloat(measurements.chest) || null,
        waist: parseFloat(measurements.waist) || null,
        hips: parseFloat(measurements.hips) || null,
        height: parseFloat(measurements.height) || null,
        weight: parseFloat(measurements.weight) || null
      });
      
      setSuggestedSize(response.data.suggested_size);
      setSelectedSize(response.data.suggested_size);
      toast.success(`تم حفظ المقاسات! المقاس المقترح: ${response.data.suggested_size}`);
      setShowMeasurements(false);
    } catch (error) {
      toast.error(error.response?.data?.detail || "فشل في حفظ المقاسات");
    }
  };

  const enhancePrompt = async () => {
    if (!prompt.trim()) {
      toast.error("الرجاء إدخال وصف التصميم");
      return;
    }

    setEnhancing(true);
    try {
      const response = await axios.post(`${API}/designs/enhance-prompt`, {
        prompt: prompt,
        clothing_type: selectedClothingType
      });
      setEnhancedPrompt(response.data.enhanced_prompt);
      setPrompt(response.data.enhanced_prompt); 
      toast.success("تم تحسين الوصف بنجاح!");
    } catch (error) {
      toast.error(error.response?.data?.detail || "فشل في تحسين الوصف");
    } finally {
      setEnhancing(false);
    }
  };

  const handleGenerate = async () => {
    if (!prompt.trim()) {
      toast.error("الرجاء إدخال وصف التصميم");
      return;
    }
    
    if (!designsQuota.is_unlimited && designsQuota.designs_remaining <= 0) {
      toast.error("لقد وصلت إلى الحد الأقصى لعدد التصاميم. تواصل مع الإدارة لزيادة الحد.");
      return;
    }

    setGenerating(true);
    setCompositeImage(null);
    setShowComposite(false);
    
    try {
      const finalPrompt = enhancedPrompt || prompt;
      
      const payload = {
        prompt: finalPrompt,
        clothing_type: selectedClothingType,
        template_id: null,
        logo_base64: logoPreview ? logoPreview.split(',')[1] : null,
        logo_position: selectedLogoPosition,
        user_photo_base64: userPhotoPreview ? userPhotoPreview.split(',')[1] : null,
        view_angle: selectedViewAngle
      };

      const response = await axios.post(`${API}/designs/preview`, payload);
      
      setGeneratedDesign({
        image_base64: response.data.image_base64,
        prompt: finalPrompt,
        clothing_type: selectedClothingType,
        template_id: null
      });
      
      if (response.data.composite_image_base64) {
        setCompositeImage(response.data.composite_image_base64);
        toast.success("🎨 تم إنشاء التصميم مع صورتك!");
      } else {
        toast.success("تم إنشاء التصميم بنجاح!");
      }
      
      if (response.data.designs_remaining !== undefined) {
        setDesignsQuota(prev => ({
          ...prev,
          designs_used: response.data.designs_used,
          designs_remaining: response.data.designs_remaining,
          designs_limit: response.data.designs_limit
        }));
      } else {
        await fetchDesignsQuota();
      }
      
      const remaining = response.data.designs_remaining ?? (designsQuota.designs_remaining - 1);
      if (!designsQuota.is_unlimited && remaining <= 3 && remaining > 0) {
        toast.warning(`⚠️ تبقى لديك ${remaining} تصاميم فقط`);
      }
      if (!designsQuota.is_unlimited && remaining <= 0) {
        toast.error("⚠️ لقد استنفدت جميع محاولات التصميم المجانية!");
      }
    } catch (error) {
      toast.error(error.response?.data?.detail || "فشل في إنشاء التصميم");
    } finally {
      setGenerating(false);
    }
  };

  const handleSaveToGallery = async () => {
    if (!generatedDesign) return;
    
    if (!phoneNumber.trim()) {
      toast.error("الرجاء إدخال رقم هاتفك للتواصل معك لاحقاً");
      setShowOrderForm(true);
      return;
    }
    
    try {
      const response = await axios.post(`${API}/designs/save`, {
        prompt: generatedDesign.prompt,
        image_base64: generatedDesign.image_base64,
        clothing_type: generatedDesign.clothing_type,
        template_id: generatedDesign.template_id,
        phone_number: phoneNumber,
        user_photo_base64: userPhotoPreview ? userPhotoPreview.split(',')[1] : null,
        logo_base64: logoPreview ? logoPreview.split(',')[1] : null
      });
      
      setDesigns([response.data.design || response.data, ...designs]);
      toast.success("✨ تم حفظ التصميم في معرضك بنجاح!");
      setPhoneNumber(""); 
    } catch (error) {
      toast.error(error.response?.data?.detail || "فشل في حفظ التصميم");
    }
  };

  const handleSubmitOrder = async () => {
    if (!phoneNumber.trim()) {
      toast.error("الرجاء إدخال رقم الهاتف");
      return;
    }

    setSubmittingOrder(true);
    try {
      await axios.post(`${API}/orders/create`, {
        design_image_base64: generatedDesign.image_base64,
        prompt: generatedDesign.prompt,
        phone_number: phoneNumber,
        size: selectedSize,
        design_id: generatedDesign.template_id,
        coupon_code: appliedCoupon ? couponCode : null
      });
      
      toast.success("تم إرسال الطلب بنجاح! سنتواصل معك قريباً");
      setShowOrderForm(false);
      setPhoneNumber("");
      
      fetchOrders();
    } catch (error) {
      toast.error(error.response?.data?.detail || "فشل في إرسال الطلب");
    } finally {
      setSubmittingOrder(false);
    }
  };

  const resetDesigner = () => {
    setPrompt("");
    setEnhancedPrompt("");
    setLogoPreview(null);
    setUserPhotoPreview(null);
    setGeneratedDesign(null);
    setCompositeImage(null);
    setShowComposite(false);
    setShowOrderForm(false);
    setSelectedViewAngle("front");
    setSelectedLogoPosition("center");
    setSelectedClothingType(null);
    setDesignStep("select-type");
  };
  
  const handleClothingTypeSelect = (type) => {
    setSelectedClothingType(type.value);
    setDesignStep("customize");
    toast.success(`تم اختيار ${type.label}! أدخل تفاصيل التصميم`);
  };
  
  const goBackToTypeSelection = () => {
    setDesignStep("select-type");
    setSelectedClothingType(null);
    setGeneratedDesign(null);
    setPrompt("");
    setEnhancedPrompt("");
  };

  const toggleFavorite = async (designId, currentStatus) => {
    try {
      const response = await axios.put(`${API}/designs/${designId}/favorite`);
      setDesigns(designs.map(d => 
        d.id === designId ? { ...d, is_favorite: response.data.is_favorite } : d
      ));
      toast.success(response.data.is_favorite ? "تمت إضافة التصميم للمفضلة" : "تمت إزالة التصميم من المفضلة");
    } catch (error) {
      toast.error(error.response?.data?.detail || "فشل في تحديث المفضلة");
    }
  };

  const handleDelete = async () => {
    try {
      await axios.delete(`${API}/designs/${deleteDialog.designId}`);
      setDesigns(designs.filter(d => d.id !== deleteDialog.designId));
      toast.success("تم حذف التصميم بنجاح");
      fetchDesignsQuota(); 
    } catch (error) {
      toast.error(error.response?.data?.detail || "فشل في حذف التصميم");
    } finally {
      setDeleteDialog({ open: false, designId: null });
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#F5F0E8] via-[#E8DCC8] to-[#F5F0E8] dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950" data-testid="dashboard-page">
      {/* Header */}
      <header className="glass border-b border-[#3E2723]/10 dark:border-zinc-800 sticky top-0 z-50 backdrop-blur-xl">
        <div className="container mx-auto px-3 sm:px-6 py-2.5 sm:py-4 flex justify-between items-center gap-2">
          <div className="flex items-center gap-2 sm:gap-3 min-w-0">
            <div className="p-1.5 sm:p-2 bg-gradient-to-br from-[#D4AF37] to-[#B8941F] rounded-lg sm:rounded-xl shadow-lg flex-shrink-0">
              <Sparkles className="w-5 h-5 sm:w-6 sm:h-6 text-white" />
            </div>
            <div className="min-w-0">
              <h1 className="text-base sm:text-xl md:text-2xl font-bold text-[#3E2723] dark:text-zinc-100 truncate">استوديو التصميم</h1>
              <p className="text-xs text-[#5D4037] dark:text-zinc-400 hidden sm:block truncate">مرحباً، {user?.username}</p>
            </div>
          </div>
          
          <div className="flex items-center gap-1 sm:gap-2 flex-shrink-0">
            {/* Designs Quota Badge */}
            {!designsQuota.is_unlimited && (
              <div className={`px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg flex-shrink-0 ${
                designsQuota.designs_remaining === 0 
                  ? 'bg-red-100 border border-red-300 dark:bg-red-900/30 dark:border-red-800' 
                  : designsQuota.designs_remaining <= 3
                  ? 'bg-orange-100 border border-orange-300 dark:bg-orange-900/30 dark:border-orange-800'
                  : 'bg-green-100 border border-green-300 dark:bg-green-900/30 dark:border-green-800'
              }`}>
                <p className="text-[10px] sm:text-xs font-bold text-center whitespace-nowrap">
                  {designsQuota.designs_remaining === 0 ? (
                    <span className="text-red-600 dark:text-red-400">انتهت</span>
                  ) : (
                    <>
                      <span className={designsQuota.designs_remaining <= 3 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400'}>
                        {designsQuota.designs_remaining}
                      </span>
                      <span className="text-[#5D4037] dark:text-zinc-400">/{designsQuota.designs_limit}</span>
                    </>
                  )}
                </p>
                <p className="text-[9px] sm:text-[10px] text-[#5D4037] dark:text-zinc-400 text-center hidden lg:block">تصميم متبقي</p>
              </div>
            )}
            {/* Notifications Bell */}
            <div className="relative">
              <Button
                onClick={() => setShowNotifications(!showNotifications)}
                variant="outline"
                size="icon"
                className="border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white relative h-9 w-9 sm:h-10 sm:w-10 dark:hover:bg-[#D4AF37] dark:hover:text-black"
              >
                <Bell className="w-4 h-4 sm:w-5 sm:h-5" />
                {unreadCount > 0 && (
                  <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center text-[10px] sm:text-xs animate-pulse">
                    {unreadCount}
                  </span>
                )}
              </Button>
              
              {/* Notifications Backdrop (Mobile) */}
              {showNotifications && (
                <div 
                  className="fixed inset-0 bg-black/50 z-40 md:hidden"
                  onClick={() => setShowNotifications(false)}
                />
              )}
              
              {/* Notifications Dropdown */}
              {showNotifications && (
                <div className="fixed md:absolute bottom-0 md:bottom-auto left-0 md:left-auto right-0 md:right-0 md:mt-2 w-full md:w-96 glass dark:bg-zinc-900 rounded-t-2xl md:rounded-xl shadow-2xl z-50 max-h-[70vh] md:max-h-[32rem] overflow-hidden flex flex-col" dir="rtl">
                  {/* Header with Close Button */}
                  <div className="flex items-center justify-between p-4 border-b border-[#3E2723]/10 dark:border-zinc-800 bg-gradient-to-l from-[#D4AF37]/10 to-[#B8941F]/10">
                    <div className="flex items-center gap-2">
                      <Bell className="w-5 h-5 text-[#D4AF37]" />
                      <h3 className="font-bold text-[#3E2723] dark:text-zinc-100 text-base">الإشعارات</h3>
                      {unreadCount > 0 && (
                        <span className="bg-[#D4AF37] text-white text-xs px-2 py-0.5 rounded-full">
                          {unreadCount} جديد
                        </span>
                      )}
                    </div>
                    <Button
                      onClick={() => setShowNotifications(false)}
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 rounded-full md:hidden dark:text-zinc-400"
                    >
                      <X className="w-4 h-4" />
                    </Button>
                  </div>
                  
                  {/* Notifications List */}
                  <div className="overflow-y-auto flex-1">
                    {notifications.length === 0 ? (
                      <div className="p-8 text-center">
                        <Bell className="w-12 h-12 mx-auto mb-3 text-[#5D4037]/30 dark:text-zinc-600" />
                        <p className="text-[#5D4037] dark:text-zinc-400 text-sm">لا توجد إشعارات حالياً</p>
                      </div>
                    ) : (
                      <div className="divide-y divide-[#3E2723]/10 dark:divide-zinc-800">
                        {notifications.map((notif) => (
                          <div
                            key={notif.id}
                            className={`p-4 cursor-pointer hover:bg-[#D4AF37]/10 active:bg-[#D4AF37]/20 transition-colors ${
                              !notif.is_read ? 'bg-[#D4AF37]/5' : ''
                            }`}
                            onClick={() => {
                              markNotificationAsRead(notif.id);
                              setShowNotifications(false);
                            }}
                          >
                            <div className="flex items-start gap-3">
                              <div className={`mt-1.5 w-2.5 h-2.5 rounded-full flex-shrink-0 ${!notif.is_read ? 'bg-[#D4AF37] animate-pulse' : 'bg-gray-300 dark:bg-zinc-700'}`} />
                              <div className="flex-1 min-w-0">
                                <div className="flex items-start justify-between gap-2 mb-1">
                                  <h4 className="font-semibold text-[#3E2723] dark:text-zinc-100 text-sm leading-tight">{notif.title}</h4>
                                  {!notif.is_read && (
                                    <span className="text-[10px] bg-[#D4AF37] text-white px-1.5 py-0.5 rounded-full flex-shrink-0">جديد</span>
                                  )}
                                </div>
                                <p className="text-sm text-[#5D4037] dark:text-zinc-400 leading-relaxed mb-2">{notif.message}</p>
                                <p className="text-xs text-[#5D4037]/70 dark:text-zinc-500 flex items-center gap-1">
                                  <span>🕐</span>
                                  {new Date(notif.created_at).toLocaleDateString('ar-EG', {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                  })}
                                </p>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                  
                  {/* Footer with Mark All as Read */}
                  {notifications.some(n => !n.is_read) && (
                    <div className="p-3 border-t border-[#3E2723]/10 dark:border-zinc-800 bg-white/50 dark:bg-zinc-900/50">
                      <button
                        onClick={async () => {
                          await markAllNotificationsAsRead();
                          setShowNotifications(false);
                        }}
                        className="w-full text-center text-sm text-[#D4AF37] hover:text-[#B8941F] font-semibold py-2"
                      >
                        وضع علامة مقروء على الكل
                      </button>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Dark Mode Toggle */}
            <Button
              onClick={toggleTheme}
              variant="outline"
              size="icon"
              className="border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white h-9 w-9 sm:h-10 sm:w-10 flex-shrink-0 dark:hover:bg-[#D4AF37] dark:hover:text-black"
              aria-label="تبديل الوضع الليلي"
            >
              {isDark ? <Sun className="w-4 h-4 sm:w-5 sm:h-5" /> : <Moon className="w-4 h-4 sm:w-5 sm:h-5" />}
            </Button>

            {/* Desktop Buttons */}
            <Button
              onClick={() => {
                resetDesigner();
                setActiveView("showcase");
              }}
              variant="outline"
              className="border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white hidden lg:flex h-9 sm:h-10 text-sm whitespace-nowrap dark:hover:bg-[#D4AF37] dark:hover:text-black"
            >
              <Sparkles className="ml-2 w-3 h-3 sm:w-4 sm:h-4" />
              تصميم جديد
            </Button>
            <Button
              onClick={onLogout}
              variant="outline"
              className="border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white hidden lg:flex h-9 sm:h-10 text-sm whitespace-nowrap dark:hover:bg-[#D4AF37] dark:hover:text-black"
            >
              <LogOut className="ml-2 w-3 h-3 sm:w-4 sm:h-4" />
              خروج
            </Button>
            
            {/* Mobile Logout Button (Icon Only) */}
            <Button
              onClick={onLogout}
              variant="outline"
              size="icon"
              className="border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white h-9 w-9 sm:h-10 sm:w-10 lg:hidden flex-shrink-0 dark:hover:bg-[#D4AF37] dark:hover:text-black"
              aria-label="تسجيل الخروج"
            >
              <LogOut className="w-4 h-4 sm:w-5 sm:h-5" />
            </Button>
          </div>
        </div>
      </header>

      <div className="container mx-auto px-3 sm:px-4 py-4 sm:py-8">
        {/* Navigation Tabs */}
        <div className="glass dark:bg-zinc-900/50 rounded-xl sm:rounded-2xl p-1.5 sm:p-2 mb-6 sm:mb-8 overflow-x-auto scrollbar-hide">
          <div className="flex gap-1.5 sm:gap-2 min-w-max sm:min-w-0">
          <button
            onClick={() => setActiveView("showcase")}
            className={`flex-shrink-0 sm:flex-1 py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg sm:rounded-xl font-semibold transition-all whitespace-nowrap text-sm sm:text-base ${
              activeView === "showcase"
                ? "bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white shadow-lg"
                : "text-[#5D4037] dark:text-zinc-400 hover:bg-white/50 dark:hover:bg-zinc-800"
            }`}
          >
            <TrendingUp className="inline ml-1.5 sm:ml-2 w-3.5 h-3.5 sm:w-4 sm:h-4" />
            تصاميم ملهمة
          </button>
          <button
            onClick={() => setActiveView("customize")}
            className={`flex-shrink-0 sm:flex-1 py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg sm:rounded-xl font-semibold transition-all whitespace-nowrap text-sm sm:text-base ${
              activeView === "customize"
                ? "bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white shadow-lg"
                : "text-[#5D4037] dark:text-zinc-400 hover:bg-white/50 dark:hover:bg-zinc-800"
            }`}
          >
            تخصيص التصميم
          </button>
          <button
            onClick={() => setActiveView("gallery")}
            className={`flex-shrink-0 sm:flex-1 py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg sm:rounded-xl font-semibold transition-all whitespace-nowrap text-sm sm:text-base ${
              activeView === "gallery"
                ? "bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white shadow-lg"
                : "text-[#5D4037] dark:text-zinc-400 hover:bg-white/50 dark:hover:bg-zinc-800"
            }`}
          >
            معرضي ({designs.length})
          </button>
          <button
            onClick={() => setActiveView("orders")}
            className={`flex-shrink-0 sm:flex-1 py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg sm:rounded-xl font-semibold transition-all whitespace-nowrap text-sm sm:text-base ${
              activeView === "orders"
                ? "bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white shadow-lg"
                : "text-[#5D4037] dark:text-zinc-400 hover:bg-white/50 dark:hover:bg-zinc-800"
            }`}
          >
            <Truck className="inline ml-1.5 sm:ml-2 w-3.5 h-3.5 sm:w-4 sm:h-4" />
            طلباتي ({orders.length})
          </button>
          <button
            onClick={() => setActiveView("coupons")}
            className={`flex-shrink-0 sm:flex-1 py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg sm:rounded-xl font-semibold transition-all whitespace-nowrap text-sm sm:text-base ${
              activeView === "coupons"
                ? "bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white shadow-lg"
                : "text-[#5D4037] dark:text-zinc-400 hover:bg-white/50 dark:hover:bg-zinc-800"
            }`}
          >
            <Tag className="inline ml-1.5 sm:ml-2 w-3.5 h-3.5 sm:w-4 sm:h-4" />
            الكوبونات
          </button>
          </div>
        </div>

        {/* Showcase View */}
        {activeView === "showcase" && (
          <div className="fade-in">
            <div className="text-center mb-6 sm:mb-8">
              <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-[#3E2723] dark:text-zinc-100 mb-2 sm:mb-3">تصاميم ناجحة تلهمك</h2>
              <p className="text-base sm:text-lg text-[#5D4037] dark:text-zinc-400">اكتشف أفضل التصاميم من مصممين آخرين</p>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
              {showcaseDesigns.map((design) => (
                <Card key={design.id} className="glass dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 overflow-hidden card-hover group">
                  <div className="relative aspect-square bg-white dark:bg-zinc-800">
                    {/* ✅ استخدام image_url و formatImageSrc للتحسين */}
                    <img
                      src={design.image_url || formatImageSrc(design.image_base64)}
                      alt={design.title}
                      className="w-full h-full object-cover"
                      loading="lazy"
                    />
                    {design.is_featured && (
                      <div className="absolute top-2 right-2 bg-[#D4AF37] text-black px-2 sm:px-3 py-1 rounded-full text-xs font-bold shadow-md">
                        مميز
                      </div>
                    )}
                  </div>
                  <CardContent className="p-3 sm:p-4">
                    <h3 className="font-bold text-[#3E2723] dark:text-zinc-100 mb-1 text-sm sm:text-base">{design.title}</h3>
                    <p className="text-xs sm:text-sm text-[#5D4037] dark:text-zinc-400 line-clamp-2 mb-2">{design.description}</p>
                    <div className="flex items-center justify-between">
                      <span className="text-xs text-[#5D4037] dark:text-zinc-500">❤️ {design.likes_count} إعجاب</span>
                      <Button
                        size="sm"
                        variant="ghost"
                        className="text-[#D4AF37] hover:text-white hover:bg-[#D4AF37] dark:hover:text-black text-xs sm:text-sm h-8"
                        onClick={() => {
                          setActiveView("customize");
                          setDesignStep("select-type");
                        }}
                      >
                        ابدأ التصميم
                      </Button>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
            
            <div className="mt-8 sm:mt-12 text-center">
              <Button
                onClick={() => {
                  setActiveView("customize");
                  setDesignStep("select-type");
                }}
                className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#967818] text-white dark:text-black font-bold text-base sm:text-lg px-8 sm:px-12 py-4 sm:py-6"
              >
                <Sparkles className="ml-2 w-4 h-4 sm:w-5 sm:h-5" />
                ابدأ تصميمك الخاص
              </Button>
            </div>
          </div>
        )}

        {/* Customize View - Step 1: Select Clothing Type */}
        {activeView === "customize" && designStep === "select-type" && (
          <div className="fade-in">
            <div className="text-center mb-8 sm:mb-12">
              <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-[#3E2723] dark:text-zinc-100 mb-3 sm:mb-4">
                🎨 ماذا تريد أن تصمم؟
              </h2>
              <p className="text-base sm:text-lg text-[#5D4037] dark:text-zinc-400 max-w-2xl mx-auto">
                اختر نوع الملابس الذي تريد تصميمه، ثم سنساعدك في إنشاء تصميم فريد بالذكاء الاصطناعي
              </p>
            </div>
            
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 max-w-5xl mx-auto">
              {CLOTHING_TYPES.map((type) => (
                <div
                  key={type.value}
                  onClick={() => type.active && handleClothingTypeSelect(type)}
                  className={`glass dark:bg-zinc-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 relative overflow-hidden transition-all duration-300 border-2 ${
                    type.active 
                      ? 'cursor-pointer group hover:scale-105 hover:shadow-2xl hover:shadow-[#D4AF37]/20 border-transparent hover:border-[#D4AF37] dark:hover:border-[#D4AF37]' 
                      : 'cursor-not-allowed opacity-60 border-gray-300 dark:border-zinc-700'
                  }`}
                >
                  {/* قيد التطوير Badge */}
                  {!type.active && (
                    <div className="absolute top-2 right-2 left-2 z-10">
                      <div className="bg-gradient-to-r from-orange-500 to-amber-500 text-white text-[10px] sm:text-xs font-bold py-1 px-2 sm:px-3 rounded-full text-center shadow-lg">
                        🚧 قيد التطوير
                      </div>
                    </div>
                  )}
                  
                  <div className={`w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-3 sm:mb-4 rounded-full bg-gradient-to-br ${type.active ? type.color : 'from-gray-300 to-gray-400 dark:from-zinc-700 dark:to-zinc-800'} flex items-center justify-center shadow-lg ${type.active ? 'group-hover:scale-110' : ''} transition-transform duration-300 ${!type.active ? 'mt-4' : ''}`}>
                    <span className={`text-3xl sm:text-4xl ${!type.active ? 'grayscale opacity-70' : ''}`}>{type.emoji}</span>
                  </div>
                  <h3 className={`text-lg sm:text-xl font-bold text-center mb-1 sm:mb-2 ${type.active ? 'text-[#3E2723] dark:text-zinc-100' : 'text-gray-500 dark:text-zinc-500'}`}>
                    {type.label}
                  </h3>
                  <p className={`text-xs sm:text-sm text-center line-clamp-2 ${type.active ? 'text-[#5D4037] dark:text-zinc-400' : 'text-gray-400 dark:text-zinc-500'}`}>
                    {type.active ? type.description : 'سيتوفر قريباً...'}
                  </p>
                  <div className="mt-3 sm:mt-4 flex justify-center">
                    {type.active ? (
                      <span className="text-xs sm:text-sm text-[#D4AF37] font-semibold opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center gap-1">
                        <Sparkles className="w-3 h-3 sm:w-4 sm:h-4" />
                        ابدأ التصميم
                      </span>
                    ) : (
                      <span className="text-xs sm:text-sm text-gray-400 dark:text-zinc-500 font-medium flex items-center gap-1">
                        ⏳ قريباً
                      </span>
                    )}
                  </div>
                </div>
              ))}
            </div>
            
            <div className="mt-8 sm:mt-12 text-center">
              <p className="text-sm text-[#5D4037] dark:text-zinc-400 mb-4">
                💡 نصيحة: اختر النوع الذي يناسب احتياجاتك، يمكنك التغيير لاحقاً
              </p>
              <p className="text-xs text-[#5D4037]/70 dark:text-zinc-500">
                🚀 المزيد من الأنواع قادمة قريباً!
              </p>
            </div>
          </div>
        )}

        {/* Customize View - Step 2: Design Details */}
        {activeView === "customize" && designStep === "customize" && selectedClothingType && (
          <div className="fade-in">
            <div className="glass dark:bg-zinc-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-2xl dark:border dark:border-zinc-800">
              <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 sm:mb-6 gap-3">
                <div className="w-full sm:w-auto flex items-center gap-3">
                  <button
                    onClick={goBackToTypeSelection}
                    className="p-2 rounded-full hover:bg-[#D4AF37]/10 transition-colors"
                  >
                    <ArrowRight className="w-5 h-5 sm:w-6 sm:h-6 text-[#D4AF37]" />
                  </button>
                  <div>
                    <h2 className="text-xl sm:text-2xl md:text-3xl font-bold text-[#3E2723] dark:text-zinc-100 flex items-center gap-2">
                      <span>{CLOTHING_TYPES.find(t => t.value === selectedClothingType)?.emoji}</span>
                      تصميم {CLOTHING_TYPES.find(t => t.value === selectedClothingType)?.label}
                    </h2>
                    <p className="text-sm sm:text-base text-[#5D4037] dark:text-zinc-400">أدخل تفاصيل التصميم الذي تريده</p>
                  </div>
                </div>
                <div className="flex gap-2 w-full sm:w-auto">
                  <Button
                    variant="outline"
                    onClick={goBackToTypeSelection}
                    className="flex-1 sm:flex-none text-xs sm:text-sm h-9 sm:h-10 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                  >
                    تغيير النوع
                  </Button>
                  <Button
                    variant="outline"
                    onClick={() => setShowSizeChart(true)}
                    className="border-[#D4AF37] text-[#D4AF37] flex-1 sm:flex-none text-xs sm:text-sm h-9 sm:h-10 dark:hover:bg-[#D4AF37] dark:hover:text-black"
                  >
                    <Ruler className="ml-1 sm:ml-2 w-3 h-3 sm:w-4 sm:h-4" />
                    <span className="hidden sm:inline">جدول المقاسات</span>
                    <span className="sm:hidden">المقاسات</span>
                  </Button>
                </div>
              </div>

              <div className="grid lg:grid-cols-2 gap-4 sm:gap-6 md:gap-8">
                {/* Left: Customization Options */}
                <div className="space-y-4 sm:space-y-6">
                  {/* Design Description */}
                  <div>
                    <Label className="text-base sm:text-lg font-semibold text-[#3E2723] dark:text-zinc-200 mb-2 sm:mb-3 block">
                      وصف التصميم
                    </Label>
                    <Textarea
                      value={prompt}
                      onChange={(e) => setPrompt(e.target.value)}
                      placeholder={`صف تصميم ${CLOTHING_TYPES.find(t => t.value === selectedClothingType)?.label} الذي تريده بالتفصيل...`}
                      className="min-h-[80px] sm:min-h-[100px] text-sm sm:text-base md:text-lg border-2 border-[#D4AF37]/30 focus:border-[#D4AF37] dark:bg-zinc-800/50 dark:text-white"
                    />
                  </div>

                  {/* View Angle Selector */}
                  <div>
                    <Label className="text-base sm:text-lg font-semibold text-[#3E2723] dark:text-zinc-200 mb-2 sm:mb-3 block flex items-center">
                      <Eye className="ml-1.5 sm:ml-2 w-4 h-4 sm:w-5 sm:h-5" />
                      زاوية العرض
                    </Label>
                    <div className="grid grid-cols-3 gap-2">
                      {VIEW_ANGLES.map((angle) => (
                        <button
                          key={angle.value}
                          onClick={() => setSelectedViewAngle(angle.value)}
                          className={`p-3 sm:p-4 rounded-lg sm:rounded-xl border-2 transition-all ${
                            selectedViewAngle === angle.value
                              ? 'border-[#D4AF37] bg-[#D4AF37]/10 text-[#D4AF37]'
                              : 'border-gray-300 dark:border-zinc-700 dark:text-zinc-400 hover:border-[#D4AF37]/50'
                          }`}
                        >
                          <div className="text-2xl sm:text-3xl mb-0.5 sm:mb-1">{angle.icon}</div>
                          <div className="text-xs sm:text-sm font-semibold">{angle.label}</div>
                        </button>
                      ))}
                    </div>
                  </div>

                  {/* Size Selector */}
                  <div>
                    <div className="flex items-center justify-between mb-2 sm:mb-3">
                      <Label className="text-base sm:text-lg font-semibold text-[#3E2723] dark:text-zinc-200 flex items-center">
                        <Ruler className="ml-1.5 sm:ml-2 w-4 h-4 sm:w-5 sm:h-5" />
                        المقاس
                      </Label>
                      <Button
                        variant="link"
                        onClick={() => setShowMeasurements(true)}
                        className="text-[#D4AF37] text-xs sm:text-sm p-0 h-auto"
                      >
                        أدخل مقاساتك
                      </Button>
                    </div>
                    <div className="grid grid-cols-3 sm:grid-cols-6 gap-2">
                      {SIZES.map((size) => (
                        <button
                          key={size}
                          onClick={() => setSelectedSize(size)}
                          className={`p-2.5 sm:p-3 rounded-lg border-2 font-bold transition-all text-sm sm:text-base ${
                            selectedSize === size
                              ? 'border-[#D4AF37] bg-[#D4AF37] text-white dark:text-black'
                              : 'border-gray-300 dark:border-zinc-700 dark:text-zinc-400 hover:border-[#D4AF37]'
                          }`}
                        >
                          {size}
                        </button>
                      ))}
                    </div>
                  </div>

                  {/* Upload Images Section */}
                  <div className="space-y-3">
                    <Label className="text-base sm:text-lg font-semibold text-[#3E2723] dark:text-zinc-200 block">
                      إضافات احترافية 🎨
                    </Label>
                    
                    {/* Info Banner */}
                    <div className="bg-gradient-to-r from-[#D4AF37]/10 to-[#B8941F]/10 border border-[#D4AF37]/30 rounded-lg p-3">
                      <p className="text-xs sm:text-sm text-[#5D4037] dark:text-zinc-300">
                        💡 <span className="font-semibold">نصيحة احترافية:</span> ارفع صورتك لرؤية التصميم عليك بشكل واقعي، وارفع شعارك ليُطبع على الملابس بجودة عالية
                      </p>
                    </div>
                    
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      {/* User Photo Upload */}
                      <div>
                        <Label className="text-sm font-medium text-[#5D4037] dark:text-zinc-400 mb-2 block">
                          📸 صورتك الشخصية
                        </Label>
                        <p className="text-[10px] text-[#5D4037]/70 dark:text-zinc-500 mb-2">جرّب التصميم عليك بشكل واقعي</p>
                        <div className="relative">
                          <Input
                            type="file"
                            accept="image/*"
                            onChange={(e) => {
                              const file = e.target.files?.[0];
                              if (file) {
                                const reader = new FileReader();
                                reader.onloadend = () => {
                                  setUserPhotoPreview(reader.result);
                                  toast.success("تم رفع صورتك");
                                };
                                reader.readAsDataURL(file);
                              }
                            }}
                            className="hidden"
                            id="user-photo-upload"
                          />
                          <label
                            htmlFor="user-photo-upload"
                            className="flex items-center justify-center gap-2 p-3 border-2 border-dashed border-[#D4AF37]/50 rounded-lg hover:border-[#D4AF37] hover:bg-[#D4AF37]/5 cursor-pointer transition-all dark:bg-zinc-800/50"
                          >
                            {userPhotoPreview ? (
                              <div className="flex flex-col items-center gap-1 w-full">
                                <img src={userPhotoPreview} alt="Preview" className="w-16 h-16 rounded-full object-cover border-2 border-[#D4AF37]" />
                                <span className="text-xs text-[#D4AF37] font-semibold">✓ جاهز</span>
                                <span className="text-[9px] text-[#5D4037] dark:text-zinc-400">سيظهر التصميم عليك</span>
                              </div>
                            ) : (
                              <>
                                <Phone className="w-4 h-4 text-[#D4AF37]" />
                                <span className="text-xs sm:text-sm text-[#5D4037] dark:text-zinc-400">ارفع صورتك</span>
                              </>
                            )}
                          </label>
                        </div>
                      </div>

                      {/* Logo Upload - قيد التطوير */}
                      <div className="relative">
                        <Label className="text-sm font-medium text-[#5D4037] dark:text-zinc-400 mb-2 block">
                          🎨 شعار/لوجو مخصص
                        </Label>
                        <p className="text-[10px] text-[#5D4037]/70 dark:text-zinc-500 mb-2">سيُطبع بجودة احترافية على الملابس</p>
                        <div className="relative opacity-50 pointer-events-none">
                          <div className="flex items-center justify-center gap-2 p-3 border-2 border-dashed border-gray-300 dark:border-zinc-700 rounded-lg bg-gray-50 dark:bg-zinc-800">
                            <Sparkles className="w-4 h-4 text-gray-400" />
                            <span className="text-xs sm:text-sm text-gray-400">ارفع الشعار</span>
                          </div>
                        </div>
                        {/* شارة قيد التطوير */}
                        <div className="absolute top-0 right-0 left-0 flex justify-center">
                          <span className="bg-gradient-to-r from-orange-500 to-amber-500 text-white text-[10px] font-bold py-1 px-3 rounded-full shadow-lg">
                            🚧 قيد التطوير
                          </span>
                        </div>
                      </div>
                    </div>

                    {/* Phone Number Input */}
                    <div>
                      <Label className="text-sm font-medium text-[#5D4037] dark:text-zinc-400 mb-2 block">
                        رقم الهاتف للتواصل
                      </Label>
                      <Input
                        type="tel"
                        value={phoneNumber}
                        onChange={(e) => setPhoneNumber(e.target.value)}
                        placeholder="05xxxxxxxx"
                        className="border-2 border-[#D4AF37]/30 focus:border-[#D4AF37] dark:bg-zinc-800/50"
                      />
                    </div>

                    {/* Coupon Code Input */}
                    <div>
                      <Label className="text-sm font-medium text-[#5D4037] dark:text-zinc-400 mb-2 block">
                        🎟️ كود الخصم (اختياري)
                      </Label>
                      <div className="flex gap-2">
                        <Input
                          type="text"
                          value={couponCode}
                          onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                          placeholder="أدخل كود الخصم"
                          disabled={appliedCoupon}
                          className="border-2 border-[#D4AF37]/30 focus:border-[#D4AF37] flex-1 dark:bg-zinc-800/50"
                        />
                        {!appliedCoupon ? (
                          <Button
                            onClick={validateCouponCode}
                            disabled={validatingCoupon || !couponCode.trim()}
                            variant="outline"
                            className="border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white px-3 dark:hover:text-black"
                          >
                            {validatingCoupon ? (
                              <Loader2 className="w-4 h-4 animate-spin" />
                            ) : (
                              "تطبيق"
                            )}
                          </Button>
                        ) : (
                          <Button
                            onClick={removeCoupon}
                            variant="outline"
                            className="border-red-400 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 px-3"
                          >
                            <X className="w-4 h-4" />
                          </Button>
                        )}
                      </div>
                      
                      {/* Applied Coupon Badge */}
                      {appliedCoupon && (
                        <div className="mt-2 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-lg p-2.5 flex items-center justify-between">
                          <div className="flex items-center gap-2">
                            <span className="text-green-600 dark:text-green-400 text-lg">✓</span>
                            <div>
                              <p className="text-green-800 dark:text-green-300 font-semibold text-sm">تم تطبيق الكوبون!</p>
                              <p className="text-green-600 dark:text-green-400 text-xs">خصم {appliedCoupon.discount_percentage}% على طلبك</p>
                            </div>
                          </div>
                          <span className="bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                            {appliedCoupon.discount_percentage}%
                          </span>
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <Button
                      onClick={enhancePrompt}
                      disabled={enhancing || !prompt.trim()}
                      variant="outline"
                      className="border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white h-10 sm:h-11 text-sm sm:text-base dark:hover:text-black"
                    >
                      {enhancing ? (
                        <Loader2 className="ml-1.5 sm:ml-2 w-3.5 h-3.5 sm:w-4 sm:h-4 animate-spin" />
                      ) : (
                        <Sparkles className="ml-1.5 sm:ml-2 w-3.5 h-3.5 sm:w-4 sm:h-4" />
                      )}
                      تحسين الوصف
                    </Button>

                    <Button
                      onClick={handleGenerate}
                      disabled={generating || !prompt.trim()}
                      className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#967818] text-white dark:text-black font-bold h-10 sm:h-11 text-sm sm:text-base"
                    >
                      {generating ? (
                        <Loader2 className="ml-1.5 sm:ml-2 w-4 h-4 sm:w-5 sm:h-5 animate-spin" />
                      ) : (
                        <Sparkles className="ml-1.5 sm:ml-2 w-4 h-4 sm:w-5 sm:h-5" />
                      )}
                      إنشاء التصميم
                    </Button>
                  </div>

                  {enhancedPrompt && (
                    <div className="p-3 sm:p-4 bg-[#D4AF37]/10 rounded-lg sm:rounded-xl border border-[#D4AF37]/30">
                      <p className="text-xs sm:text-sm font-semibold text-[#3E2723] dark:text-zinc-200 mb-1 sm:mb-2">الوصف المحسّن:</p>
                      <p className="text-[#5D4037] dark:text-zinc-300 text-xs sm:text-sm">{enhancedPrompt}</p>
                    </div>
                  )}
                </div>

                {/* Right: Design Preview */}
                <div className="space-y-3 sm:space-y-4">
                  {/* Toggle between design and composite */}
                  {generatedDesign && compositeImage && (
                    <div className="flex justify-center gap-2 mb-2">
                      <Button
                        onClick={() => setShowComposite(false)}
                        variant={!showComposite ? "default" : "outline"}
                        size="sm"
                        className={!showComposite 
                          ? "bg-[#D4AF37] text-white dark:text-black" 
                          : "border-[#D4AF37] text-[#D4AF37]"
                        }
                      >
                        🎨 التصميم
                      </Button>
                      <Button
                        onClick={() => setShowComposite(true)}
                        variant={showComposite ? "default" : "outline"}
                        size="sm"
                        className={showComposite 
                          ? "bg-[#D4AF37] text-white dark:text-black" 
                          : "border-[#D4AF37] text-[#D4AF37]"
                        }
                      >
                        👤 صورتك مع التصميم
                      </Button>
                    </div>
                  )}
                  
                  <div className={`w-full bg-gradient-to-br from-[#D4AF37]/5 to-[#B8941F]/5 rounded-2xl sm:rounded-3xl border-2 border-dashed border-[#D4AF37]/30 flex items-center justify-center overflow-hidden ${
                    showComposite && compositeImage ? 'aspect-[2/1]' : 'aspect-square'
                  }`}>
                    {generatedDesign ? (
                      showComposite && compositeImage ? (
                        <img 
                          src={formatImageSrc(compositeImage)}
                          alt="Your Photo with Design" 
                          className="w-full h-full object-contain"
                        />
                      ) : (
                        <img 
                          src={formatImageSrc(generatedDesign.image_base64)}
                          alt="Generated Design" 
                          className="w-full h-full object-contain"
                        />
                      )
                    ) : (
                      <div className="text-center p-4">
                        <Sparkles className="w-12 h-12 sm:w-16 sm:h-16 md:w-20 md:h-20 text-[#D4AF37] mx-auto mb-3 sm:mb-4 opacity-50" />
                        <p className="text-[#5D4037] dark:text-zinc-400 text-sm sm:text-base md:text-lg">سيظهر تصميمك هنا</p>
                        {userPhotoPreview && (
                          <p className="text-[#D4AF37] text-xs mt-2">📸 ستظهر صورتك مع التصميم</p>
                        )}
                        {logoPreview && (
                          <p className="text-[#D4AF37] text-xs mt-1">🎨 سيُدمج شعارك في التصميم</p>
                        )}
                      </div>
                    )}
                  </div>

                  {/* Info banner when composite is available */}
                  {generatedDesign && compositeImage && (
                    <div className="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 text-center">
                      <p className="text-sm text-green-800 dark:text-green-300 font-medium">
                        ✨ تم دمج صورتك مع التصميم بنجاح!
                      </p>
                      <p className="text-xs text-green-600 dark:text-green-400 mt-1">
                        انقر على زر &quot;صورتك مع التصميم&quot; لرؤية النتيجة النهائية
                      </p>
                    </div>
                  )}

                  {/* Actions when design is generated */}
                  {generatedDesign && !showOrderForm && (
                    <div className="space-y-2 sm:space-y-3">
                      <Button
                        onClick={handleSaveToGallery}
                        className="w-full bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#967818] text-white dark:text-black py-3 sm:py-4 hover:scale-105 hover:shadow-2xl transition-all duration-300 group relative overflow-hidden text-sm sm:text-base"
                      >
                        <span className="absolute inset-0 bg-gradient-to-r from-yellow-400 to-amber-500 opacity-0 group-hover:opacity-20 transition-opacity duration-300"></span>
                        <Save className="ml-1.5 sm:ml-2 w-4 h-4 sm:w-5 sm:h-5 group-hover:animate-bounce" />
                        <span className="font-bold">حفظ في معرضي</span>
                      </Button>
                      <Button
                        onClick={() => setShowOrderForm(true)}
                        variant="outline"
                        className="w-full border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white dark:hover:text-black py-3 sm:py-4 hover:scale-105 transition-all duration-300 text-sm sm:text-base"
                      >
                        <ShoppingCart className="ml-1.5 sm:ml-2 w-4 h-4 sm:w-5 sm:h-5" />
                        أعجبني! أريد طلبه
                      </Button>
                    </div>
                  )}

                  {/* Simple Order Form */}
                  {showOrderForm && (
                    <div className="glass dark:bg-zinc-900 rounded-xl sm:rounded-2xl p-4 sm:p-6 space-y-3 sm:space-y-4 fade-in">
                      <div className="flex items-center justify-between mb-1 sm:mb-2">
                        <h3 className="text-lg sm:text-xl font-bold text-[#3E2723] dark:text-zinc-100">إتمام الطلب</h3>
                        <button 
                          onClick={() => setShowOrderForm(false)}
                          className="text-[#5D4037] dark:text-zinc-400 hover:text-[#3E2723] dark:hover:text-white p-1"
                        >
                          <X className="w-5 h-5" />
                        </button>
                      </div>

                      <div className="p-3 sm:p-4 bg-[#D4AF37]/10 rounded-lg">
                        <div className="flex justify-between">
                          <span className="text-[#5D4037] dark:text-zinc-300 text-sm sm:text-base">المقاس:</span>
                          <span className="font-bold text-[#3E2723] dark:text-zinc-100 text-sm sm:text-base">{selectedSize}</span>
                        </div>
                      </div>

                      <div>
                        <Label className="text-xs sm:text-sm font-semibold text-[#3E2723] dark:text-zinc-200 mb-2 block">
                          <Phone className="inline ml-1.5 sm:ml-2 w-3.5 h-3.5 sm:w-4 sm:h-4" />
                          رقم الهاتف للتواصل
                        </Label>
                        <Input
                          type="tel"
                          value={phoneNumber}
                          onChange={(e) => setPhoneNumber(e.target.value)}
                          placeholder="05xxxxxxxx"
                          className="w-full text-base sm:text-lg h-11 sm:h-12 dark:bg-zinc-800/50"
                          dir="ltr"
                        />
                      </div>

                      <Button
                        onClick={handleSubmitOrder}
                        disabled={submittingOrder || !phoneNumber.trim()}
                        className="w-full bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#967818] text-white dark:text-black font-bold py-3 sm:py-4 text-sm sm:text-base"
                      >
                        {submittingOrder ? (
                          <>
                            <Loader2 className="ml-1.5 sm:ml-2 w-4 h-4 sm:w-5 sm:h-5 animate-spin" />
                            جاري الإرسال...
                          </>
                        ) : (
                          <>
                            <Package className="ml-1.5 sm:ml-2 w-4 h-4 sm:w-5 sm:h-5" />
                            إرسال الطلب
                          </>
                        )}
                      </Button>
                      
                      <p className="text-xs text-center text-[#5D4037] dark:text-zinc-500">
                        سيتم التواصل معك خلال 24 ساعة لتأكيد الطلب والدفع
                      </p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* My Orders View */}
        {activeView === "orders" && (
          <div className="fade-in">
            <div className="text-center mb-6 sm:mb-8">
              <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-[#3E2723] dark:text-zinc-100 mb-2 sm:mb-3">طلباتي</h2>
              <p className="text-base sm:text-lg text-[#5D4037] dark:text-zinc-400">تتبع حالة طلباتك ({orders.length})</p>
            </div>

            {orders.length === 0 ? (
              <div className="glass dark:bg-zinc-900 rounded-2xl sm:rounded-3xl p-8 sm:p-12 text-center">
                <Truck className="w-12 h-12 sm:w-16 sm:h-16 text-[#D4AF37] mx-auto mb-3 sm:mb-4" />
                <p className="text-lg sm:text-xl text-[#5D4037] dark:text-zinc-400 mb-3 sm:mb-4">لا توجد طلبات بعد</p>
                <Button
                  onClick={() => setActiveView("showcase")}
                  className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#967818] text-white dark:text-black text-sm sm:text-base font-bold"
                >
                  ابدأ الطلب الأول
                </Button>
              </div>
            ) : (
              <div className="space-y-4 sm:space-y-6">
                {orders.map((order) => (
                  <Card key={order.id} className="glass dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 overflow-hidden">
                    <CardContent className="p-4 sm:p-6">
                      <div className="flex flex-col sm:flex-row gap-4 sm:gap-6" dir="rtl">
                        {/* Order Image */}
                        <div className="w-full sm:w-24 md:w-32 h-24 sm:h-24 md:h-32 flex-shrink-0 rounded-lg sm:rounded-xl overflow-hidden bg-white dark:bg-zinc-800">
                          {/* ✅ استخدام image_url و formatImageSrc للتحسين */}
                          <img
                            src={order.image_url || formatImageSrc(order.design_image_base64)}
                            alt="Design"
                            className="w-full h-full object-cover"
                            loading="lazy"
                          />
                        </div>

                        {/* Order Details */}
                        <div className="flex-1 space-y-2 sm:space-y-3 min-w-0">
                          <div className="flex flex-col sm:flex-row justify-between items-start gap-2">
                            <div className="flex-1 min-w-0">
                              <h3 className="font-bold text-[#3E2723] dark:text-zinc-100 text-base sm:text-lg mb-1">
                                طلب #{String(order.id).substring(0, 8)}
                              </h3>
                              <p className="text-xs sm:text-sm text-[#5D4037] dark:text-zinc-400 line-clamp-2">{order.prompt}</p>
                            </div>
                            <span
                              className={`px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-bold whitespace-nowrap ${
                                order.status === "pending"
                                  ? "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400"
                                  : order.status === "processing"
                                  ? "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
                                  : order.status === "completed"
                                  ? "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"
                                  : "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                              }`}
                            >
                              {order.status === "pending"
                                ? "قيد الانتظار"
                                : order.status === "processing"
                                ? "قيد التنفيذ"
                                : order.status === "completed"
                                ? "مكتمل"
                                : "ملغي"}
                            </span>
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-4 text-xs sm:text-sm">
                            <div>
                              <span className="text-[#5D4037] dark:text-zinc-500">المقاس:</span>{" "}
                              <span className="font-bold text-[#3E2723] dark:text-zinc-200">{order.size || "غير محدد"}</span>
                            </div>
                            <div>
                              <span className="text-[#5D4037] dark:text-zinc-500">رقم الهاتف:</span>{" "}
                              <span className="font-bold text-[#3E2723] dark:text-zinc-200">{order.phone_number}</span>
                            </div>
                            <div>
                              <span className="text-[#5D4037] dark:text-zinc-500">التاريخ:</span>{" "}
                              <span className="font-bold text-[#3E2723] dark:text-zinc-200">
                                {new Date(order.created_at).toLocaleDateString("ar-EG")}
                              </span>
                            </div>
                          </div>

                          {order.notes && (
                            <div className="pt-3 border-t border-[#3E2723]/10 dark:border-zinc-800">
                              <div className="text-sm text-[#5D4037] dark:text-zinc-400 italic">
                                &ldquo;{order.notes}&rdquo;
                              </div>
                            </div>
                          )}
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Coupons View */}
        {activeView === "coupons" && (
          <div className="fade-in">
            <div className="text-center mb-6 sm:mb-8">
              <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-[#3E2723] dark:text-zinc-100 mb-2 sm:mb-3">الكوبونات المتاحة</h2>
              <p className="text-base sm:text-lg text-[#5D4037] dark:text-zinc-400">احصل على خصومات رائعة</p>
            </div>

            {availableCoupons.length === 0 ? (
              <div className="glass dark:bg-zinc-900 rounded-2xl sm:rounded-3xl p-8 sm:p-12 text-center">
                <Tag className="w-12 h-12 sm:w-16 sm:h-16 text-[#D4AF37] mx-auto mb-3 sm:mb-4" />
                <p className="text-lg sm:text-xl text-[#5D4037] dark:text-zinc-400">لا توجد كوبونات متاحة حالياً</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                {availableCoupons.map((coupon, idx) => (
                  <Card
                    key={idx}
                    className="glass dark:bg-zinc-900 overflow-hidden border-2 border-[#D4AF37] hover:shadow-2xl transition-all card-hover"
                  >
                    <div className="bg-gradient-to-br from-[#D4AF37] to-[#B8941F] p-4 sm:p-6 text-white dark:text-black">
                      <Tag className="w-8 h-8 sm:w-10 sm:h-10 mb-2 sm:mb-3" />
                      <div className="text-3xl sm:text-4xl font-bold mb-1 sm:mb-2">
                        {coupon.discount_percentage}%
                      </div>
                      <div className="text-xs sm:text-sm opacity-90 font-medium">خصم على طلبك</div>
                    </div>
                    <CardContent className="p-4 sm:p-6 space-y-3 sm:space-y-4" dir="rtl">
                      <div className="text-center">
                        <div className="text-xl sm:text-2xl font-bold text-[#3E2723] dark:text-[#D4AF37] bg-[#D4AF37]/10 py-2.5 sm:py-3 px-3 sm:px-4 rounded-lg inline-block tracking-wider">
                          {coupon.code}
                        </div>
                      </div>
                      <p className="text-[#5D4037] dark:text-zinc-300 text-center text-sm sm:text-base">{coupon.description}</p>
                      {coupon.min_purchase > 0 && (
                        <p className="text-xs text-[#5D4037] dark:text-zinc-500 text-center">
                          الحد الأدنى للشراء: {coupon.min_purchase} ر.س
                        </p>
                      )}
                      {coupon.expiry_date && (
                        <p className="text-xs text-[#5D4037] dark:text-zinc-500 text-center">
                          صالح حتى: {new Date(coupon.expiry_date).toLocaleDateString("ar-EG")}
                        </p>
                      )}
                      <Button
                        onClick={() => {
                          if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(coupon.code)
                              .then(() => {
                                toast.success("تم نسخ الكوبون!");
                              })
                              .catch(() => {
                                const textArea = document.createElement("textarea");
                                textArea.value = coupon.code;
                                textArea.style.position = "fixed";
                                textArea.style.left = "-999999px";
                                document.body.appendChild(textArea);
                                textArea.focus();
                                textArea.select();
                                try {
                                  document.execCommand('copy');
                                  toast.success("تم نسخ الكوبون!");
                                } catch (err) {
                                  toast.error("فشل نسخ الكوبون");
                                }
                                document.body.removeChild(textArea);
                              });
                          } else {
                            const textArea = document.createElement("textarea");
                            textArea.value = coupon.code;
                            textArea.style.position = "fixed";
                            textArea.style.left = "-999999px";
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();
                            try {
                              document.execCommand('copy');
                              toast.success("تم نسخ الكوبون!");
                            } catch (err) {
                              toast.error("فشل نسخ الكوبون");
                            }
                            document.body.removeChild(textArea);
                          }
                        }}
                        className="w-full bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#967818] text-white dark:text-black font-bold text-sm sm:text-base h-10 sm:h-11"
                      >
                        نسخ الكود
                      </Button>
                    </CardContent>
                  </Card>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Gallery View */}
        {activeView === "gallery" && (
          <div className="fade-in">
            <div className="text-center mb-6 sm:mb-8">
              <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-[#3E2723] dark:text-zinc-100 mb-2 sm:mb-3">معرض تصاميمي</h2>
              <p className="text-base sm:text-lg text-[#5D4037] dark:text-zinc-400">جميع تصاميمك المحفوظة ({designs.length})</p>
            </div>
            
            {loading ? (
              <div className="flex justify-center items-center py-12 sm:py-20">
                <Loader2 className="w-10 h-10 sm:w-12 sm:h-12 text-[#D4AF37] animate-spin" />
              </div>
            ) : designs.length === 0 ? (
              <div className="glass dark:bg-zinc-900 rounded-2xl sm:rounded-3xl p-8 sm:p-12 text-center">
                <Sparkles className="w-12 h-12 sm:w-16 sm:h-16 text-[#D4AF37] mx-auto mb-3 sm:mb-4" />
                <p className="text-lg sm:text-xl text-[#5D4037] dark:text-zinc-400 mb-3 sm:mb-4">لا توجد تصاميم محفوظة بعد</p>
                <Button
                  onClick={() => setActiveView("showcase")}
                  className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#967818] text-white dark:text-black font-bold text-sm sm:text-base"
                >
                  <Sparkles className="ml-1.5 sm:ml-2 w-4 h-4 sm:w-5 sm:h-5" />
                  ابدأ التصميم الآن
                </Button>
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                {designs.map((design) => (
                  <Card key={design.id} className="glass dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 overflow-hidden card-hover">
                    <div className="relative aspect-square bg-white dark:bg-zinc-800">
                      {/* ✅ استخدام image_url و formatImageSrc للتحسين ومنع الأخطاء */}
                      {(design.image_url || design.image_base64) ? (
                        <img 
                          src={design.image_url || formatImageSrc(design.image_base64)} 
                          alt="التصميم" 
                          className="w-full h-full object-cover"
                          loading="lazy"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-zinc-800">
                          <div className="text-center">
                            <Sparkles className="w-8 h-8 mx-auto text-gray-400 mb-2" />
                            <span className="text-sm text-gray-500">جاري المعالجة أو الصورة غير متوفرة</span>
                          </div>
                        </div>
                      )}
                      <button
                        onClick={() => toggleFavorite(design.id, design.is_favorite)}
                        className="absolute top-2 sm:top-4 left-2 sm:left-4 p-1.5 sm:p-2 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-sm rounded-full shadow-lg hover:scale-110 transition-transform"
                      >
                        <Heart
                          className={`w-5 h-5 sm:w-6 sm:h-6 ${
                            design.is_favorite
                              ? "fill-red-500 text-red-500"
                              : "text-[#5D4037] dark:text-zinc-300"
                          }`}
                        />
                      </button>
                    </div>
                    <CardContent className="p-3 sm:p-4 space-y-2 sm:space-y-3">
                      <p className="text-[#3E2723] dark:text-zinc-200 line-clamp-2 text-sm sm:text-base">{design.prompt}</p>
                      <Button
                        onClick={() => setDeleteDialog({ open: true, designId: design.id })}
                        variant="outline"
                        size="sm"
                        className="w-full border-red-500 text-red-500 hover:bg-red-500 hover:text-white dark:hover:bg-red-900/40 text-xs sm:text-sm h-8 sm:h-9"
                      >
                        <Trash2 className="ml-1.5 sm:ml-2 w-3 h-3 sm:w-4 sm:h-4" />
                        حذف
                      </Button>
                    </CardContent>
                  </Card>
                ))}
              </div>
            )}
          </div>
        )}
      </div>

      {/* Measurements Dialog */}
      <Dialog open={showMeasurements} onOpenChange={setShowMeasurements}>
        <DialogContent className="max-w-md bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800" dir="rtl">
          <DialogHeader>
            <DialogTitle className="text-2xl font-bold text-[#3E2723] dark:text-zinc-100">
              أدخل مقاساتك
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label className="dark:text-zinc-300">محيط الصدر (سم)</Label>
              <Input
                type="number"
                value={measurements.chest}
                onChange={(e) => setMeasurements({...measurements, chest: e.target.value})}
                placeholder="95"
                className="dark:bg-zinc-800/50"
              />
            </div>
            <div>
              <Label className="dark:text-zinc-300">محيط الخصر (سم)</Label>
              <Input
                type="number"
                value={measurements.waist}
                onChange={(e) => setMeasurements({...measurements, waist: e.target.value})}
                placeholder="80"
                className="dark:bg-zinc-800/50"
              />
            </div>
            <div>
              <Label className="dark:text-zinc-300">محيط الوركين (سم)</Label>
              <Input
                type="number"
                value={measurements.hips}
                onChange={(e) => setMeasurements({...measurements, hips: e.target.value})}
                placeholder="100"
                className="dark:bg-zinc-800/50"
              />
            </div>
            <Button
              onClick={saveMeasurements}
              className="w-full bg-gradient-to-l from-[#D4AF37] to-[#B8941F] hover:from-[#B8941F] hover:to-[#967818] text-white dark:text-black font-bold"
            >
              حفظ واقتراح المقاس
            </Button>
          </div>
        </DialogContent>
      </Dialog>

      {/* Size Chart Dialog */}
      <Dialog open={showSizeChart} onOpenChange={setShowSizeChart}>
        <DialogContent className="max-w-2xl bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800" dir="rtl">
          <DialogHeader>
            <DialogTitle className="text-2xl font-bold text-[#3E2723] dark:text-zinc-100">
              جدول المقاسات
            </DialogTitle>
          </DialogHeader>
          <div className="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
            <table className="w-full text-sm">
              <thead className="bg-[#D4AF37] text-white dark:text-black">
                <tr>
                  <th className="p-3">المقاس</th>
                  <th className="p-3">الصدر (سم)</th>
                  <th className="p-3">الخصر (سم)</th>
                  <th className="p-3">الوركين (سم)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800">
                {Object.entries(sizeChart).map(([size, dims]) => (
                  <tr key={size} className="hover:bg-[#D4AF37]/10 transition-colors">
                    <td className="p-3 font-bold text-center bg-zinc-50 dark:bg-zinc-900/50 text-zinc-900 dark:text-zinc-100">{size}</td>
                    <td className="p-3 text-center text-zinc-700 dark:text-zinc-300">{dims.chest}</td>
                    <td className="p-3 text-center text-zinc-700 dark:text-zinc-300">{dims.waist}</td>
                    <td className="p-3 text-center text-zinc-700 dark:text-zinc-300">{dims.hips}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <p className="text-xs text-zinc-500 mt-4 text-center">
            * هذه المقاسات تقريبية وقد تختلف قليلاً حسب نوع القطعة.
          </p>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={deleteDialog.open} onOpenChange={(open) => setDeleteDialog({ open, designId: null })}>
        <AlertDialogContent dir="rtl" className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800">
          <AlertDialogHeader>
            <AlertDialogTitle className="text-red-500 flex items-center gap-2">
              <Trash2 className="w-5 h-5" /> هل أنت متأكد من الحذف؟
            </AlertDialogTitle>
            <AlertDialogDescription className="text-zinc-600 dark:text-zinc-400 text-base mt-2">
              سيتم حذف التصميم نهائياً ولا يمكن استرجاعه.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter className="gap-3 sm:gap-0 mt-6">
            <AlertDialogCancel onClick={() => setDeleteDialog({open: false, designId: null})} className="mt-0 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">تراجع</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              className="bg-red-500 hover:bg-red-600 text-white font-bold"
            >
              حذف التصميم
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}