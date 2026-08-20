import { useState, useEffect } from "react";
import axios from "axios";
import { toast } from "sonner";
import {
  Users, Package, Image as ImageIcon, Tag, TrendingUp, DollarSign,
  Edit, Trash2, Plus, X, Search, CheckCircle, Clock,
  XCircle, ShoppingCart, Phone, Mail, Calendar, Award, Sparkles, Eye, UserX, ChevronLeft, ChevronRight
} from "lucide-react";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "../components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import ShowcaseManager from "../components/ShowcaseManager";

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

export default function AdminDashboard({ user, onLogout }) {
  const [activeTab, setActiveTab] = useState("overview");
  const [loading, setLoading] = useState(false);

  // Stats
  const [stats, setStats] = useState({
    total_users: 0,
    total_orders: 0,
    total_designs: 0,
    pending_orders: 0,
    completed_orders: 0,
    total_revenue: 0,
    total_discounts_given: 0,
    total_showcase: 0
  });

  // Data Arrays
  const [usersList, setUsersList] = useState([]);
  const [orders, setOrders] = useState([]);
  const [designs, setDesigns] = useState([]);
  const [coupons, setCoupons] = useState([]);

  // Pagination States for Orders
  const [orderCurrentPage, setOrderCurrentPage] = useState(1);
  const [orderTotalPages, setOrderTotalPages] = useState(1);

  // Pagination States for Designs
  const [designCurrentPage, setDesignCurrentPage] = useState(1);
  const [designTotalPages, setDesignTotalPages] = useState(1);

  // Pagination States for Users (New Backend Update Fix)
  const [userCurrentPage, setUserCurrentPage] = useState(1);
  const [userTotalPages, setUserTotalPages] = useState(1);

  // Pagination States for Coupons (New Backend Update Fix)
  const [couponCurrentPage, setCouponCurrentPage] = useState(1);
  const [couponTotalPages, setCouponTotalPages] = useState(1);

  // Modals & Search
  const [searchQuery, setSearchQuery] = useState("");
  const [editUserModal, setEditUserModal] = useState({ open: false, user: null });
  const [deleteUserModal, setDeleteUserModal] = useState({ open: false, user: null });
  const [isOrderModalOpen, setIsOrderModalOpen] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState(null);

  const [isCouponModalOpen, setIsCouponModalOpen] = useState(false);
  const [couponForm, setCouponForm] = useState({
    code: '', discount_percentage: '', expires_at: '', max_uses: 100, is_active: true
  });

  // Modal for Viewing Full Design Image
  const [isImageModalOpen, setIsImageModalOpen] = useState(false);
  const [selectedImage, setSelectedImage] = useState(null);

  // Modal for Viewing Coupon Usages
  const [couponUsageModal, setCouponUsageModal] = useState({ isOpen: false, usages: [], couponCode: '' });

  // Initial Data Load
  useEffect(() => {
    if (activeTab === "overview") fetchStats();
    if (activeTab === "users") fetchUsers(userCurrentPage);
    if (activeTab === "orders") fetchOrders(orderCurrentPage);
    if (activeTab === "designs") fetchDesigns(designCurrentPage);
    if (activeTab === "coupons") fetchCoupons(couponCurrentPage);
  }, [activeTab]);

  // Orders Pagination Effect
  useEffect(() => {
    if (activeTab === "orders") fetchOrders(orderCurrentPage);
  }, [orderCurrentPage]);

  // Designs Pagination Effect
  useEffect(() => {
     if (activeTab === "designs") fetchDesigns(designCurrentPage);
  }, [designCurrentPage]);

  // Users Pagination Effect
  useEffect(() => {
    if (activeTab === "users") fetchUsers(userCurrentPage);
  }, [userCurrentPage]);

  // Coupons Pagination Effect
  useEffect(() => {
    if (activeTab === "coupons") fetchCoupons(couponCurrentPage);
  }, [couponCurrentPage]);

  const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    return { headers: { Authorization: `Bearer ${token}` } };
  };

  const getErrorMessage = (error, defaultMessage) => {
    return error.response?.data?.detail || defaultMessage;
  };

  // ✅ دالة مساعدة لمعالجة مسار الصورة (رابط مباشر أو Base64)
  const getImageSource = (url, base64Data) => {
    if (url) return url;
    if (base64Data) {
      return base64Data.startsWith('data:image')
        ? base64Data
        : `data:image/png;base64,${base64Data}`;
    }
    return null;
  };

  const fetchStats = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API}/admin/stats`, getAuthHeaders());
      setStats(response.data);
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل الإحصائيات"));
    } finally {
      setLoading(false);
    }
  };

  // ✅ تم إصلاح دالة جلب المستخدمين لتتوافق مع الـ Paginator الجديد
  const fetchUsers = async (page = 1) => {
    setLoading(true);
    try {
      const response = await axios.get(`${API}/admin/users?page=${page}`, getAuthHeaders());
      // التحقق مما إذا كانت البيانات تعود عبر Paginator
      if (response.data && response.data.data) {
        setUsersList(response.data.data);
        setUserCurrentPage(response.data.current_page || 1);
        setUserTotalPages(response.data.last_page || 1);
      } else {
        setUsersList(Array.isArray(response.data) ? response.data : []);
      }
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل المستخدمين"));
      setUsersList([]); // حماية من انهيار الواجهة
    } finally {
      setLoading(false);
    }
  };

  const fetchOrders = async (page = 1) => {
    setLoading(true);
    try {
      const res = await axios.get(`${API}/admin/orders?page=${page}`, getAuthHeaders());
      if (res.data && res.data.data) {
        setOrders(res.data.data);
        setOrderCurrentPage(res.data.current_page);
        setOrderTotalPages(res.data.last_page);
      } else {
        setOrders(Array.isArray(res.data) ? res.data : []);
      }
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل الطلبات"));
    } finally {
      setLoading(false);
    }
  };

  const fetchDesigns = async (page = 1) => {
    setLoading(true);
    try {
      const res = await axios.get(`${API}/admin/designs?page=${page}`, getAuthHeaders());
       if (res.data && res.data.data) {
        setDesigns(res.data.data);
        setDesignCurrentPage(res.data.current_page);
        setDesignTotalPages(res.data.last_page);
      } else {
        setDesigns(Array.isArray(res.data) ? res.data : []);
      }
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل التصاميم"));
    } finally {
      setLoading(false);
    }
  };

  // ✅ تم إصلاح دالة جلب الكوبونات لتتوافق مع الـ Paginator الجديد
  const fetchCoupons = async (page = 1) => {
    setLoading(true);
    try {
      const res = await axios.get(`${API}/admin/coupons?page=${page}`, getAuthHeaders());
      if (res.data && res.data.data) {
        setCoupons(res.data.data);
        setCouponCurrentPage(res.data.current_page || 1);
        setCouponTotalPages(res.data.last_page || 1);
      } else {
        setCoupons(Array.isArray(res.data) ? res.data : []);
      }
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل الكوبونات"));
      setCoupons([]); // حماية
    } finally {
      setLoading(false);
    }
  };

  // ✅ تم إصلاح دالة تفاصيل الاستخدام لتجنب انهيار النافذة
  const fetchCouponUsages = async (id, code) => {
    try {
      const res = await axios.get(`${API}/admin/coupons/${id}/usage`, getAuthHeaders());
      // استخراج المصفوفة بشكل آمن سواء كان الجواب بـ pagination أو عادي
      const usagesArray = res.data?.usages?.data || res.data?.usages || [];
      setCouponUsageModal({
        isOpen: true,
        usages: Array.isArray(usagesArray) ? usagesArray : [],
        couponCode: code
      });
    } catch (error) {
      try {
        const oldRes = await axios.get(`${API}/admin/coupons/${id}/usages`, getAuthHeaders());
        const fallbackUsages = oldRes.data?.usages?.data || oldRes.data?.usages || oldRes.data || [];
        setCouponUsageModal({
           isOpen: true,
           usages: Array.isArray(fallbackUsages) ? fallbackUsages : [],
           couponCode: code
        });
      } catch (err) {
        toast.error("فشل في جلب تفاصيل استخدام الكوبون");
      }
    }
  };

  const deleteUser = async (userId) => {
    try {
      const response = await axios.delete(`${API}/admin/users/${userId}`, getAuthHeaders());
      toast.success(response.data?.message || "تم حذف المستخدم بنجاح");
      fetchUsers(userCurrentPage);
      fetchStats();
      setDeleteUserModal({ open: false, user: null });
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في حذف المستخدم"));
    }
  };

  const updateUserLimit = async (userId, payload) => {
    try {
      const response = await axios.put(`${API}/admin/users/${userId}/designs-limit`, payload, getAuthHeaders());
      toast.success(response.data?.message || "تم تحديث حد التصاميم");
      fetchUsers(userCurrentPage);
      setEditUserModal({ open: false, user: null });
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحديث الحد"));
    }
  };

  const handleUpdateOrderStatus = async (id, status) => {
    try {
      await axios.put(`${API}/admin/orders/${id}/status`, { status }, getAuthHeaders());
      toast.success("تم تحديث حالة الطلب");
      fetchOrders(orderCurrentPage);
      fetchStats();
      setIsOrderModalOpen(false);
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحديث حالة الطلب"));
    }
  };

  const handleDeleteDesign = async (id) => {
    if (!window.confirm("هل أنت متأكد من حذف هذا التصميم؟")) return;
    try {
      await axios.delete(`${API}/admin/designs/${id}`, getAuthHeaders());
      toast.success("تم حذف التصميم");
      fetchDesigns(designCurrentPage);
      fetchStats();
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في حذف التصميم"));
    }
  };

  const handleCreateCoupon = async (e) => {
    e.preventDefault();
    try {
      await axios.post(`${API}/admin/coupons`, couponForm, getAuthHeaders());
      toast.success("تم إنشاء الكوبون بنجاح");
      setIsCouponModalOpen(false);
      fetchCoupons(couponCurrentPage);
      setCouponForm({ code: '', discount_percentage: '', expires_at: '', max_uses: 100, is_active: true });
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في إنشاء الكوبون"));
    }
  };

  const handleDeleteCoupon = async (id) => {
    if (!window.confirm("هل أنت متأكد من حذف هذا الكوبون؟")) return;
    try {
      await axios.delete(`${API}/admin/coupons/${id}`, getAuthHeaders());
      toast.success("تم حذف الكوبون");
      fetchCoupons(couponCurrentPage);
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في حذف الكوبون"));
    }
  };

  // ✅ نافذة الصورة المباشرة
  const openImageModal = (imageSrc) => {
    if (!imageSrc) return;
    setSelectedImage(imageSrc);
    setIsImageModalOpen(true);
  };

  const getStatusBadge = (status) => {
    const styles = {
      pending: "bg-yellow-100 text-yellow-800",
      processing: "bg-blue-100 text-blue-800",
      completed: "bg-green-100 text-green-800",
      cancelled: "bg-red-100 text-red-800"
    };
    const labels = {
      pending: "قيد الانتظار",
      processing: "قيد المعالجة",
      completed: "مكتمل",
      cancelled: "ملغي"
    };
    return (
      <span className={`px-2 py-1 rounded-full text-xs font-semibold ${styles[status] || styles.pending}`}>
        {labels[status] || status}
      </span>
    );
  };

  // Filter Data (مع حماية المصفوفات للتأكد من أنها لا تنهار)
  const safeUsersList = Array.isArray(usersList) ? usersList : [];
  const filteredUsers = safeUsersList.filter(u =>
    u.username?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    u.email?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const safeOrdersList = Array.isArray(orders) ? orders : [];
  const filteredOrders = safeOrdersList.filter(o =>
    o.id?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    o.user_name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    o.user_info?.username?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    o.prompt?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    o.coupon_code?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const safeCouponsList = Array.isArray(coupons) ? coupons : [];

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#F5F0E8] via-[#E8DCC8] to-[#F5F0E8]" dir="rtl">
      <header className="glass border-b border-[#3E2723]/10 sticky top-0 z-40">
        <div className="container mx-auto px-4 py-4 flex justify-between items-center">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-gradient-to-br from-[#D4AF37] to-[#B8941F] rounded-xl">
              <Award className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-[#3E2723]">لوحة تحكم المدير</h1>
              <p className="text-sm text-[#5D4037]">مرحباً، {user?.username}</p>
            </div>
          </div>
          <Button onClick={onLogout} variant="outline" className="border-[#3E2723]">
            خروج
          </Button>
        </div>
      </header>

      <div className="container mx-auto px-4 py-8">
        <div className="glass rounded-2xl p-2 mb-8">
          <div className="flex gap-2 overflow-x-auto hide-scrollbar">
            {[
              { id: "overview", label: "نظرة عامة", shortLabel: "عامة", icon: TrendingUp },
              { id: "users", label: "المستخدمين", shortLabel: "المستخدمين", icon: Users },
              { id: "orders", label: "الطلبات", shortLabel: "الطلبات", icon: Package },
              { id: "designs", label: "التصاميم", shortLabel: "التصاميم", icon: ImageIcon },
              { id: "showcase", label: "التصاميم الملهمة", shortLabel: "ملهمة", icon: Sparkles },
              { id: "coupons", label: "الكوبونات", shortLabel: "كوبونات", icon: Tag }
            ].map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center gap-1 sm:gap-2 px-3 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold transition-all whitespace-nowrap text-xs sm:text-sm ${
                  activeTab === tab.id
                    ? "bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white shadow-lg"
                    : "text-[#5D4037] hover:bg-white/50"
                }`}
              >
                <tab.icon className="w-4 h-4" />
                <span className="hidden sm:inline">{tab.label}</span>
                <span className="sm:hidden">{tab.shortLabel}</span>
              </button>
            ))}
          </div>
        </div>

        {/* TAB: OVERVIEW */}
        {activeTab === "overview" && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 animate-fade-in-up">
            <Card className="glass border-[#D4AF37]/30">
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-[#5D4037]">إجمالي المستخدمين</CardTitle>
                <Users className="w-4 h-4 text-[#D4AF37]" />
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-[#3E2723]">{stats.total_users}</div>
              </CardContent>
            </Card>

            <Card className="glass border-[#D4AF37]/30">
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-[#5D4037]">إجمالي الطلبات</CardTitle>
                <ShoppingCart className="w-4 h-4 text-[#D4AF37]" />
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-[#3E2723]">{stats.total_orders}</div>
                <p className="text-xs text-[#5D4037] mt-1">
                  قيد الانتظار: {stats.pending_orders} | مكتمل: {stats.completed_orders}
                </p>
              </CardContent>
            </Card>

            <Card className="glass border-[#D4AF37]/30">
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-[#5D4037]">الإيرادات الكلية</CardTitle>
                <DollarSign className="w-4 h-4 text-[#D4AF37]" />
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-[#3E2723]">{parseFloat(stats.total_revenue || 0).toFixed(2)} ر.س</div>
              </CardContent>
            </Card>

            <Card className="glass border-[#D4AF37]/30">
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-[#5D4037]">إجمالي الخصومات</CardTitle>
                <Tag className="w-4 h-4 text-[#D4AF37]" />
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-[#3E2723]">{parseFloat(stats.total_discounts_given || 0).toFixed(2)} ر.س</div>
              </CardContent>
            </Card>
          </div>
        )}

        {/* TAB: USERS */}
        {activeTab === "users" && (
          <div className="animate-fade-in-up">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-bold text-[#3E2723]">إدارة المستخدمين</h2>
              <div className="relative">
                <Search className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5D4037]" />
                <Input
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="بحث..."
                  className="pr-10 w-64 bg-white/50 border-[#D4AF37]/30 focus:border-[#D4AF37]"
                />
              </div>
            </div>

            <div className="glass rounded-2xl overflow-hidden hidden md:block">
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead className="bg-[#D4AF37]/10">
                    <tr>
                      <th className="px-4 py-3 text-right text-sm font-semibold text-[#3E2723]">اسم المستخدم</th>
                      <th className="px-4 py-3 text-right text-sm font-semibold text-[#3E2723]">البريد</th>
                      <th className="px-4 py-3 text-right text-sm font-semibold text-[#3E2723]">استخدام التصاميم</th>
                      <th className="px-4 py-3 text-right text-sm font-semibold text-[#3E2723]">تاريخ التسجيل</th>
                      <th className="px-4 py-3 text-right text-sm font-semibold text-[#3E2723]">إجراءات</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredUsers.length > 0 ? filteredUsers.map(u => (
                      <tr key={u.id} className="border-t border-[#3E2723]/10 hover:bg-white/30 transition-colors">
                        <td className="px-4 py-3 text-sm text-[#3E2723]">
                          <div className="flex items-center gap-2">
                            {u.username}
                            {u.is_admin && (
                              <span className="px-2 py-0.5 bg-purple-100 text-purple-800 rounded text-xs">مدير</span>
                            )}
                          </div>
                        </td>
                        <td className="px-4 py-3 text-sm text-[#5D4037]">{u.email}</td>
                        <td className="px-4 py-3 text-sm">
                          <span className={`px-2 py-1 rounded ${
                            u.is_unlimited ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'
                          }`}>
                            {u.is_unlimited ? 'غير محدود' : `${u.designs_used || 0} / ${u.designs_limit || 10}`}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-sm text-[#5D4037]">
                          {u.created_at ? new Date(u.created_at).toLocaleDateString('ar-EG') : 'غير متوفر'}
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex gap-2">
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => setEditUserModal({ open: true, user: u })}
                              className="text-xs border-[#D4AF37]/30 hover:bg-[#D4AF37]/10 text-[#3E2723]"
                            >
                              <Edit className="w-3 h-3 ml-1" />
                              تعديل
                            </Button>
                            {!u.is_admin && (
                              <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setDeleteUserModal({ open: true, user: u })}
                                className="text-xs text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                              >
                                <UserX className="w-3 h-3 ml-1" />
                                حذف
                              </Button>
                            )}
                          </div>
                        </td>
                      </tr>
                    )) : (
                      <tr><td colSpan="5" className="text-center py-6 text-[#5D4037]">لا يوجد مستخدمين مطابقين للبحث.</td></tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>

            <div className="md:hidden space-y-3">
              {filteredUsers.map(u => (
                <Card key={u.id} className="glass">
                  <CardContent className="p-4">
                    <div className="flex justify-between items-start mb-3">
                      <div>
                        <div className="flex items-center gap-2 mb-1">
                          <p className="font-bold text-[#3E2723]">{u.username}</p>
                          {u.is_admin && (
                            <span className="px-2 py-0.5 bg-purple-100 text-purple-800 rounded text-xs">مدير</span>
                          )}
                        </div>
                        <p className="text-sm text-[#5D4037]">{u.email}</p>
                      </div>
                      <span className={`px-2 py-1 rounded text-xs font-semibold ${
                        u.is_unlimited ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'
                      }`}>
                        {u.is_unlimited ? 'غير محدود' : `${u.designs_used || 0}/${u.designs_limit || 10}`}
                      </span>
                    </div>

                    <div className="flex items-center justify-between text-xs text-[#5D4037] mb-3">
                      <span className="flex items-center gap-1">
                        <Calendar className="w-3 h-3" />
                        {u.created_at ? new Date(u.created_at).toLocaleDateString('ar-EG') : 'غير متوفر'}
                      </span>
                    </div>

                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => setEditUserModal({ open: true, user: u })}
                        className="text-xs flex-1 border-[#D4AF37]/30 text-[#3E2723]"
                      >
                        <Edit className="w-3 h-3 ml-1" />
                        تعديل الحد
                      </Button>
                      {!u.is_admin && (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => setDeleteUserModal({ open: true, user: u })}
                          className="text-xs text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200 flex-none"
                        >
                          <UserX className="w-3 h-3" />
                        </Button>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>

            {/* ✅ Users Pagination */}
            {userTotalPages > 1 && (
              <div className="flex justify-center items-center mt-8 gap-4">
                <Button
                  onClick={() => setUserCurrentPage(prev => Math.max(prev - 1, 1))}
                  disabled={userCurrentPage === 1}
                  variant="outline"
                  className="glass border-[#D4AF37]/50 text-[#3E2723]"
                >
                  <ChevronRight className="w-4 h-4 mr-1" /> السابق
                </Button>
                <span className="text-sm text-[#5D4037] font-semibold px-4 py-2 glass rounded-lg">
                  صفحة {userCurrentPage} من {userTotalPages}
                </span>
                <Button
                  onClick={() => setUserCurrentPage(prev => Math.min(prev + 1, userTotalPages))}
                  disabled={userCurrentPage === userTotalPages}
                  variant="outline"
                  className="glass border-[#D4AF37]/50 text-[#3E2723]"
                >
                  التالي <ChevronLeft className="w-4 h-4 ml-1" />
                </Button>
              </div>
            )}
          </div>
        )}

        {/* TAB: ORDERS */}
        {activeTab === "orders" && (
          <div className="animate-fade-in-up">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
              <h2 className="text-xl sm:text-2xl font-bold text-[#3E2723]">إدارة الطلبات</h2>
              <Input
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="بحث برقم الطلب، الإيميل، الوصف..."
                className="w-full sm:w-80 bg-white/50 border-[#D4AF37]/30 focus:border-[#D4AF37]"
              />
            </div>

            <div className="grid gap-4">
              {filteredOrders.length > 0 ? filteredOrders.map(order => {
                // ✅ استخدام الدالة المساعدة لضمان الحصول على مسار الصورة الصحيح
                const imgSrc = getImageSource(order.design_image_url, order.design_image_base64);

                return (
                <Card key={order.id} className="glass overflow-hidden hover:shadow-lg transition-shadow">
                  <CardContent className="p-3 sm:p-4">
                    <div className="flex flex-col sm:flex-row gap-4">
                      <div
                        className="relative w-full sm:w-32 h-48 sm:h-32 rounded-lg overflow-hidden cursor-pointer group flex-shrink-0 bg-gray-100 flex items-center justify-center"
                        onClick={() => openImageModal(imgSrc)}
                      >
                        {imgSrc ? (
                          <>
                            <img
                              src={imgSrc}
                              alt="Design"
                              className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                            />
                            <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                              <Eye className="text-white" size={24} />
                            </div>
                          </>
                        ) : (
                          <ImageIcon size={32} className="text-[#D7CCC8]" />
                        )}
                      </div>

                      <div className="flex-1 flex flex-col justify-between">
                        <div>
                          <div className="flex justify-between items-start mb-2">
                            <div>
                              <p className="font-bold text-[#3E2723]">{order.user_name || order.user_info?.username || "غير متوفر"}</p>
                              <p className="text-sm text-[#5D4037] line-clamp-1">{order.prompt}</p>
                            </div>
                            {getStatusBadge(order.status)}
                          </div>

                          <div className="flex flex-wrap items-center gap-3 text-xs text-[#5D4037] mt-1 bg-white/40 p-2 rounded-lg">
                            {order.size && <span><span className="text-[#8D6E63]">المقاس:</span> <span className="font-bold">{order.size}</span></span>}
                            {order.color && <span className="border-r border-[#D4AF37]/30 pr-3"><span className="text-[#8D6E63]">اللون:</span> <span className="font-bold">{order.color}</span></span>}
                            <span className="border-r border-[#D4AF37]/30 pr-3">
                               <span className="text-[#8D6E63]">السعر:</span> <span className="font-bold text-[#3E2723]">{parseFloat(order.final_price || order.price || 0).toFixed(2)} ر.س</span>
                            </span>
                          </div>

                          <div className="flex flex-wrap gap-3 text-xs text-[#5D4037] mt-3">
                            <span className="flex items-center gap-1">
                              <Phone className="w-3 h-3" />
                              {order.phone_number || "غير متوفر"}
                            </span>
                            <span className="flex items-center gap-1">
                              <Calendar className="w-3 h-3" />
                              {order.created_at ? new Date(order.created_at).toLocaleDateString('ar-EG') : ''}
                            </span>
                            {order.coupon_code && (
                               <span className="flex items-center gap-1 font-semibold text-green-700 bg-green-100 border border-green-200 px-2 py-0.5 rounded-full">
                                 <Tag className="w-3 h-3" />
                                 كوبون: {order.coupon_code}
                               </span>
                            )}
                          </div>
                        </div>

                        <div className="mt-3 sm:mt-0 sm:flex sm:justify-end">
                          <Button
                            className="w-full sm:w-auto bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white hover:opacity-90 rounded-lg text-sm h-8 shadow-sm"
                            onClick={() => {
                              setSelectedOrder(order);
                              setIsOrderModalOpen(true);
                            }}
                          >
                            تحديث حالة الطلب
                          </Button>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}) : (
                <div className="text-center py-10 text-[#5D4037]">لا توجد طلبات مطابقة.</div>
              )}
            </div>

            {/* Order Pagination */}
            {orderTotalPages > 1 && (
              <div className="flex justify-center items-center mt-8 gap-4">
                <Button
                  onClick={() => setOrderCurrentPage(prev => Math.max(prev - 1, 1))}
                  disabled={orderCurrentPage === 1}
                  variant="outline"
                  className="glass border-[#D4AF37]/50 text-[#3E2723]"
                >
                  <ChevronRight className="w-4 h-4 mr-1" /> السابق
                </Button>
                <span className="text-sm text-[#5D4037] font-semibold px-4 py-2 glass rounded-lg">
                  صفحة {orderCurrentPage} من {orderTotalPages}
                </span>
                <Button
                  onClick={() => setOrderCurrentPage(prev => Math.min(prev + 1, orderTotalPages))}
                  disabled={orderCurrentPage === orderTotalPages}
                  variant="outline"
                  className="glass border-[#D4AF37]/50 text-[#3E2723]"
                >
                  التالي <ChevronLeft className="w-4 h-4 ml-1" />
                </Button>
              </div>
            )}
          </div>
        )}

        {/* TAB: DESIGNS */}
        {activeTab === "designs" && (
          <div className="animate-fade-in-up">
            <h2 className="text-2xl font-bold text-[#3E2723] mb-6">جميع التصاميم</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              {Array.isArray(designs) && designs.map(design => {
                // ✅ استخدام الدالة المساعدة
                const imgSrc = getImageSource(design.image_url, design.image_base64);

                return (
                <Card key={design.id} className="glass overflow-hidden group relative border-0 shadow-sm hover:shadow-lg transition-shadow">
                  <div className="relative overflow-hidden cursor-pointer bg-gray-100 aspect-square flex items-center justify-center" onClick={() => openImageModal(imgSrc)}>
                    {imgSrc ? (
                      <>
                        <img
                          src={imgSrc}
                          alt="Design"
                          className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                        />
                        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                           <Eye className="text-white" size={32} />
                        </div>
                      </>
                    ) : (
                      <ImageIcon size={48} className="text-gray-300" />
                    )}
                  </div>
                  <CardContent className="p-4">
                    <div className="flex justify-between items-start mb-2">
                      <p className="font-semibold text-[#3E2723] truncate pr-2">{design.user_info?.username || design.user_name || "مجهول"}</p>
                      <button onClick={() => handleDeleteDesign(design.id)} className="text-red-500 hover:text-red-700 bg-red-50 p-1.5 rounded-lg transition-colors flex-shrink-0">
                         <Trash2 size={16} />
                      </button>
                    </div>
                    <p className="text-xs text-[#5D4037] mb-3 line-clamp-2" title={design.prompt}>{design.prompt}</p>
                    <div className="flex justify-between items-center text-[10px] text-[#8D6E63] border-t border-[#D4AF37]/20 pt-2">
                      {design.phone_number ? (
                        <span className="flex items-center gap-1">
                          <Phone size={12} />
                          {design.phone_number}
                        </span>
                      ) : (
                        <span></span>
                      )}
                      <span>{design.created_at ? new Date(design.created_at).toLocaleDateString('ar-EG') : ''}</span>
                    </div>
                  </CardContent>
                </Card>
              )})}
            </div>

            {/* Design Pagination */}
            {designTotalPages > 1 && (
              <div className="flex justify-center items-center mt-8 gap-4">
                <Button
                  onClick={() => setDesignCurrentPage(prev => Math.max(prev - 1, 1))}
                  disabled={designCurrentPage === 1}
                  variant="outline"
                  className="glass border-[#D4AF37]/50 text-[#3E2723]"
                >
                  <ChevronRight className="w-4 h-4 mr-1" /> السابق
                </Button>
                <span className="text-sm text-[#5D4037] font-semibold px-4 py-2 glass rounded-lg">
                  صفحة {designCurrentPage} من {designTotalPages}
                </span>
                <Button
                  onClick={() => setDesignCurrentPage(prev => Math.min(prev + 1, designTotalPages))}
                  disabled={designCurrentPage === designTotalPages}
                  variant="outline"
                  className="glass border-[#D4AF37]/50 text-[#3E2723]"
                >
                  التالي <ChevronLeft className="w-4 h-4 ml-1" />
                </Button>
              </div>
            )}
          </div>
        )}

        {/* TAB: SHOWCASE */}
        {activeTab === "showcase" && (
          <div className="animate-fade-in-up">
             <ShowcaseManager token={localStorage.getItem('token')} />
          </div>
        )}

        {/* TAB: COUPONS */}
        {activeTab === "coupons" && (
          <div className="animate-fade-in-up">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-bold text-[#3E2723]">إدارة الكوبونات</h2>
              <Button onClick={() => setIsCouponModalOpen(true)} className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white shadow-md">
                <Plus className="w-4 h-4 ml-2" />
                إضافة كوبون
              </Button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
              {safeCouponsList.map(coupon => (
                <Card key={coupon.id} className="glass relative overflow-hidden">
                  {!coupon.is_active && (
                     <div className="absolute top-0 right-0 bg-red-500 text-white text-[10px] px-2 py-1 font-bold rounded-bl-lg z-10">غير فعال</div>
                  )}
                  <CardContent className="p-4">
                    <div className="flex justify-between items-start mb-3">
                      <div>
                        <p className="text-xl font-black text-[#3E2723] uppercase tracking-wider">{coupon.code}</p>
                        <p className="text-sm font-bold text-[#D4AF37]">خصم {coupon.discount_percentage || coupon.discount_value}%</p>
                      </div>
                      <div className="flex gap-1 bg-white/50 rounded-lg p-1">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => fetchCouponUsages(coupon.id, coupon.code)}
                          className="text-blue-600 hover:text-blue-700 h-8 w-8 p-0"
                          title="عرض تفاصيل الاستخدام"
                        >
                          <Eye className="w-4 h-4" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => handleDeleteCoupon(coupon.id)}
                          className="text-red-600 hover:text-red-700 hover:bg-red-50 h-8 w-8 p-0"
                        >
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </div>
                    </div>

                    <div className="bg-[#D4AF37]/10 rounded-lg p-3 mb-3 border border-[#D4AF37]/20">
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-[#5D4037] flex items-center"><Award size={14} className="ml-1"/> الاستخدام:</span>
                        <span className="font-bold text-[#3E2723]">
                          {coupon.used_count || coupon.usage_count || coupon.current_uses || 0} / {coupon.max_uses || '∞'}
                        </span>
                      </div>
                    </div>

                    <div className="flex items-center justify-between text-xs font-medium text-[#5D4037]">
                      <span>
                        {coupon.is_active ? (
                          <span className="text-green-600 flex items-center"><CheckCircle size={12} className="ml-1"/> فعال</span>
                        ) : (
                          <span className="text-red-600 flex items-center"><XCircle size={12} className="ml-1"/> غير فعال</span>
                        )}
                      </span>
                      {(coupon.expires_at || coupon.expiry_date) && (
                        <span className="flex items-center"><Calendar size={12} className="ml-1"/> ينتهي: {new Date(coupon.expires_at || coupon.expiry_date).toLocaleDateString('ar-EG')}</span>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>

            {/* ✅ Coupons Pagination */}
            {couponTotalPages > 1 && (
              <div className="flex justify-center items-center mt-8 gap-4">
                <Button
                  onClick={() => setCouponCurrentPage(prev => Math.max(prev - 1, 1))}
                  disabled={couponCurrentPage === 1}
                  variant="outline"
                  className="glass border-[#D4AF37]/50 text-[#3E2723]"
                >
                  <ChevronRight className="w-4 h-4 mr-1" /> السابق
                </Button>
                <span className="text-sm text-[#5D4037] font-semibold px-4 py-2 glass rounded-lg">
                  صفحة {couponCurrentPage} من {couponTotalPages}
                </span>
                <Button
                  onClick={() => setCouponCurrentPage(prev => Math.min(prev + 1, couponTotalPages))}
                  disabled={couponCurrentPage === couponTotalPages}
                  variant="outline"
                  className="glass border-[#D4AF37]/50 text-[#3E2723]"
                >
                  التالي <ChevronLeft className="w-4 h-4 ml-1" />
                </Button>
              </div>
            )}
          </div>
        )}
      </div>

      {/* --- MODALS --- */}

      {/* Edit User Modal */}
      <Dialog open={editUserModal.open} onOpenChange={(open) => setEditUserModal({ open, user: null })}>
        <DialogContent className="sm:max-w-md" dir="rtl">
          <DialogHeader>
            <DialogTitle className="text-xl font-bold text-[#3E2723]">تعديل حد التصاميم</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 mt-4">
            <div className="bg-[#D4AF37]/10 p-3 rounded-lg">
              <Label className="text-[#5D4037] block mb-1">المستخدم:</Label>
              <p className="font-bold text-[#3E2723]">{editUserModal.user?.username}</p>
              <p className="text-sm text-[#8D6E63] mt-1">الحد الحالي: {editUserModal.user?.is_unlimited ? 'غير محدود' : editUserModal.user?.designs_limit || 10}</p>
            </div>
            <div>
              <Label className="text-[#5D4037] block mb-2">الحد الجديد</Label>
              <Input
                type="number"
                id="newLimit"
                defaultValue={editUserModal.user?.is_unlimited ? -1 : editUserModal.user?.designs_limit}
                placeholder="أدخل الحد الجديد (-1 لغير محدود)"
                className="border-[#D4AF37]/30 focus:border-[#D4AF37]"
              />
            </div>
          </div>
          <DialogFooter className="mt-6">
            <Button variant="outline" onClick={() => setEditUserModal({ open: false, user: null })} className="border-[#D4AF37]/30 text-[#5D4037]">
              إلغاء
            </Button>
            <Button
              onClick={() => {
                const newLimit = parseInt(document.getElementById('newLimit').value);
                const payload = newLimit === -1
                  ? { is_unlimited: true, designs_limit: 0 }
                  : { is_unlimited: false, designs_limit: newLimit };
                updateUserLimit(editUserModal.user.id, payload);
              }}
              className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white"
            >
              حفظ التعديلات
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Order Status Modal */}
      <Dialog open={isOrderModalOpen} onOpenChange={setIsOrderModalOpen}>
        <DialogContent className="sm:max-w-md p-0 overflow-hidden" dir="rtl">
          <div className="bg-gradient-to-r from-[#D4AF37] to-[#B8941F] p-6 text-white text-center relative">
             <h2 className="text-xl font-bold">تحديث حالة الطلب</h2>
             <p className="text-white/80 text-sm mt-1">طلب #{selectedOrder?.id?.substring(0,8)}</p>
          </div>
          <div className="p-6">
            <div className="mb-6 bg-[#D4AF37]/10 p-4 rounded-xl space-y-3 border border-[#D4AF37]/20">
               <div className="flex items-center text-sm font-medium text-[#3E2723]"><Phone size={16} className="ml-2 text-[#D4AF37]" /> {selectedOrder?.phone_number || "غير متوفر"}</div>
               <div className="flex items-center text-sm font-medium text-[#3E2723]"><Mail size={16} className="ml-2 text-[#D4AF37]" /> {selectedOrder?.user_email || selectedOrder?.user_info?.email || "غير متوفر"}</div>
            </div>

            <Label className="text-[#5D4037] font-bold mb-2 block">اختر الحالة الجديدة:</Label>
            <Select
              defaultValue={selectedOrder?.status}
              onValueChange={(val) => handleUpdateOrderStatus(selectedOrder.id, val)}
            >
              <SelectTrigger className="w-full h-12 rounded-xl border-[#D4AF37]/30 focus:ring-[#D4AF37]">
                <SelectValue placeholder="اختر الحالة" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="pending">قيد الانتظار</SelectItem>
                <SelectItem value="processing">قيد التنفيذ (جاري الطباعة)</SelectItem>
                <SelectItem value="completed">مكتمل (تم التوصيل)</SelectItem>
                <SelectItem value="cancelled">ملغي</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </DialogContent>
      </Dialog>

      {/* Create Coupon Form Modal */}
      <Dialog open={isCouponModalOpen} onOpenChange={setIsCouponModalOpen}>
        <DialogContent className="sm:max-w-md" dir="rtl">
          <DialogHeader>
            <DialogTitle className="text-xl font-bold text-[#3E2723]">إنشاء كوبون جديد</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleCreateCoupon} className="space-y-4 mt-4">
            <div>
              <Label className="text-[#5D4037]">رمز الكوبون (انجليزي)</Label>
              <Input
                required
                className="mt-1 uppercase border-[#D4AF37]/30 focus:border-[#D4AF37]"
                placeholder="مثال: SUMMER50"
                value={couponForm.code}
                onChange={e => setCouponForm({...couponForm, code: e.target.value.toUpperCase()})}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label className="text-[#5D4037]">نسبة الخصم (%)</Label>
                <Input
                  type="number" required min="1" max="100"
                  className="mt-1 border-[#D4AF37]/30 focus:border-[#D4AF37]"
                  value={couponForm.discount_percentage}
                  onChange={e => setCouponForm({...couponForm, discount_percentage: e.target.value})}
                />
              </div>
              <div>
                <Label className="text-[#5D4037]">الحد الأقصى للاستخدام</Label>
                <Input
                  type="number" required min="1"
                  className="mt-1 border-[#D4AF37]/30 focus:border-[#D4AF37]"
                  value={couponForm.max_uses}
                  onChange={e => setCouponForm({...couponForm, max_uses: e.target.value})}
                />
              </div>
            </div>
            <div>
              <Label className="text-[#5D4037]">تاريخ الانتهاء</Label>
              <Input
                type="date" required
                className="mt-1 border-[#D4AF37]/30 focus:border-[#D4AF37]"
                value={couponForm.expires_at}
                onChange={e => setCouponForm({...couponForm, expires_at: e.target.value})}
              />
            </div>
            <DialogFooter className="mt-6">
              <Button type="button" variant="outline" onClick={() => setIsCouponModalOpen(false)} className="border-[#D4AF37]/30 text-[#5D4037]">
                إلغاء
              </Button>
              <Button type="submit" className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F] text-white">
                حفظ وإنشاء
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Delete User Modal */}
      <Dialog open={deleteUserModal.open} onOpenChange={(open) => setDeleteUserModal({ open, user: null })}>
        <DialogContent dir="rtl">
          <DialogHeader>
            <DialogTitle className="text-red-600 flex items-center"><XCircle className="ml-2" /> تأكيد حذف المستخدم</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 mt-2">
            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
              <p className="text-red-800 font-semibold mb-2">
                هل أنت متأكد من حذف هذا المستخدم؟
              </p>
              <p className="text-red-700 text-sm">
                سيتم حذف المستخدم <strong>{deleteUserModal.user?.username}</strong> وجميع بياناته بشكل نهائي، ويشمل ذلك:
              </p>
              <ul className="text-red-700 text-sm mt-2 list-disc list-inside">
                <li>جميع التصاميم المحفوظة</li>
                <li>جميع الطلبات</li>
              </ul>
            </div>
          </div>
          <DialogFooter className="mt-4">
            <Button variant="outline" onClick={() => setDeleteUserModal({ open: false, user: null })} className="border-red-200 text-red-600">
              إلغاء
            </Button>
            <Button
              onClick={() => deleteUser(deleteUserModal.user?.id)}
              className="bg-red-600 hover:bg-red-700 text-white"
            >
              <UserX className="w-4 h-4 ml-2" />
              تأكيد الحذف
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Coupon Usage Details Modal */}
      <Dialog open={couponUsageModal.isOpen} onOpenChange={(open) => setCouponUsageModal(prev => ({...prev, isOpen: open}))}>
        <DialogContent className="sm:max-w-2xl rounded-2xl" dir="rtl">
          <DialogHeader>
            <DialogTitle className="text-xl font-bold text-[#3E2723]">
              تفاصيل استخدام الكوبون <span className="text-[#D4AF37] bg-[#D4AF37]/10 px-2 py-1 rounded-md">{couponUsageModal.couponCode}</span>
            </DialogTitle>
          </DialogHeader>
          <div className="mt-4 max-h-[60vh] overflow-y-auto pr-2">
            {!Array.isArray(couponUsageModal.usages) || couponUsageModal.usages.length === 0 ? (
              <div className="text-center bg-gray-50 rounded-xl py-12 border border-dashed border-gray-200">
                 <Tag className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                 <p className="text-[#8D6E63] font-medium">لم يتم استخدام هذا الكوبون بعد.</p>
              </div>
            ) : (
              <div className="border border-[#D4AF37]/30 rounded-xl overflow-hidden shadow-sm">
                <table className="w-full text-right">
                  <thead className="bg-[#D4AF37]/10">
                    <tr>
                      <th className="px-4 py-3 text-sm font-bold text-[#3E2723]">المستخدم</th>
                      <th className="px-4 py-3 text-sm font-bold text-[#3E2723]">البريد</th>
                      <th className="px-4 py-3 text-sm font-bold text-[#3E2723]">رقم الطلب</th>
                      <th className="px-4 py-3 text-sm font-bold text-[#3E2723]">قيمة الطلب</th>
                      <th className="px-4 py-3 text-sm font-bold text-[#3E2723]">التاريخ</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#D4AF37]/10">
                    {couponUsageModal.usages.map((usage, index) => (
                      <tr key={usage.id || index} className="hover:bg-gray-50/50 transition-colors">
                        <td className="px-4 py-3 text-sm text-[#3E2723] font-medium">{usage.user?.username || usage.username || 'مجهول'}</td>
                        <td className="px-4 py-3 text-sm text-[#5D4037]">{usage.user?.email || usage.email || 'غير متوفر'}</td>
                        <td className="px-4 py-3 text-sm text-[#5D4037]">{usage.order?.id || usage.order_id || 'N/A'}</td>
                        <td className="px-4 py-3 text-sm font-bold text-[#B8941F]">
                          {parseFloat(usage.order?.final_price || usage.order_amount || 0).toFixed(2)} ر.س
                        </td>
                        <td className="px-4 py-3 text-sm text-[#5D4037] whitespace-nowrap">
                          {usage.used_at ? new Date(usage.used_at).toLocaleDateString('ar-EG') : 'غير متوفر'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>

      {/* Image Full View Modal */}
      {/* ✅ التعديل هنا لعرض الرابط مباشرة (بدون فرض Base64) */}
      <Dialog open={isImageModalOpen} onOpenChange={setIsImageModalOpen}>
        <DialogContent className="max-w-4xl w-full p-2 rounded-2xl bg-black/95 border-0 shadow-2xl">
            <button
              onClick={() => setIsImageModalOpen(false)}
              className="absolute top-4 right-4 z-50 bg-black/50 text-white rounded-full p-2 hover:bg-red-500 hover:text-white transition-all backdrop-blur-sm"
            >
               <X size={24} />
            </button>
            {selectedImage && (
              <div className="flex items-center justify-center p-4">
                 <img src={selectedImage} alt="Full Design" className="w-full max-h-[85vh] object-contain rounded-xl" />
              </div>
            )}
        </DialogContent>
      </Dialog>

    </div>
  );
}