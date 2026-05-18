import { useState, useEffect } from "react";
import axios from "axios";
import { toast } from "sonner";
import { 
  Users, Package, Image, Tag, TrendingUp, DollarSign, 
  Edit, Trash2, Plus, X, Search, CheckCircle, Clock, 
  XCircle, ShoppingCart, Phone, Mail, Calendar, Award, Sparkles, Eye, UserX
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
    total_discounts_given: 0
  });
  
  // Data
  const [users, setUsers] = useState([]);
  const [orders, setOrders] = useState([]);
  const [designs, setDesigns] = useState([]);
  const [coupons, setCoupons] = useState([]);
  
  // Modals
  const [editUserModal, setEditUserModal] = useState({ open: false, user: null });
  const [createCouponModal, setCreateCouponModal] = useState(false);
  const [viewDesignModal, setViewDesignModal] = useState({ open: false, design: null });
  const [couponUsageModal, setCouponUsageModal] = useState({ open: false, coupon: null, usages: [] });
  const [deleteUserModal, setDeleteUserModal] = useState({ open: false, user: null });

  // New Coupon Form State
  const [newCoupon, setNewCoupon] = useState({
    code: "",
    discount_percentage: "",
    expiry_date: "",
    description: "",
    min_purchase: ""
  });
  
  // Search
  const [searchQuery, setSearchQuery] = useState("");

  useEffect(() => {
    if (activeTab === "overview") fetchStats();
    if (activeTab === "users") fetchUsers();
    if (activeTab === "orders") fetchOrders();
    if (activeTab === "designs") fetchDesigns();
    if (activeTab === "coupons") fetchCoupons();
  }, [activeTab]);

  const getErrorMessage = (error, defaultMessage) => {
    return error.response?.data?.detail || defaultMessage;
  };

  const fetchStats = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API}/admin/stats`);
      setStats(response.data);
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل الإحصائيات"));
    } finally {
      setLoading(false);
    }
  };

  const fetchUsers = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API}/admin/users`);
      setUsers(response.data);
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل المستخدمين"));
    } finally {
      setLoading(false);
    }
  };

  const fetchOrders = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API}/admin/orders`);
      setOrders(response.data);
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل الطلبات"));
    } finally {
      setLoading(false);
    }
  };

  const fetchDesigns = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API}/admin/designs`);
      setDesigns(response.data);
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل التصاميم"));
    } finally {
      setLoading(false);
    }
  };

  const fetchCoupons = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API}/admin/coupons-stats`);
      setCoupons(response.data);
    } catch (error) {
      try {
        const response = await axios.get(`${API}/admin/coupons`);
        setCoupons(response.data);
      } catch (err) {
        toast.error(getErrorMessage(err, "فشل في تحميل الكوبونات"));
        console.error("Fetch coupons error:", err);
      }
    } finally {
      setLoading(false);
    }
  };

  const fetchCouponUsage = async (couponId) => {
    try {
      const response = await axios.get(`${API}/admin/coupons/${couponId}/usage`);
      setCouponUsageModal({
        open: true,
        coupon: response.data,
        usages: response.data.usages || []
      });
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحميل إحصائيات الكوبون"));
    }
  };

  const deleteUser = async (userId) => {
    try {
      const response = await axios.delete(`${API}/admin/users/${userId}`);
      toast.success(response.data.message || "تم حذف المستخدم بنجاح");
      fetchUsers();
      setDeleteUserModal({ open: false, user: null });
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في حذف المستخدم"));
    }
  };

  const updateUserLimit = async (userId, payload) => {
    try {
      const response = await axios.put(`${API}/admin/users/${userId}/designs-limit`, payload);
      toast.success(response.data.message || "تم تحديث حد التصاميم");
      fetchUsers();
      setEditUserModal({ open: false, user: null });
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحديث الحد"));
    }
  };

  const updateOrderStatus = async (orderId, status) => {
    try {
      const response = await axios.put(`${API}/admin/orders/${orderId}/status`, { status });
      toast.success(response.data.message || "تم تحديث حالة الطلب");
      fetchOrders();
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في تحديث الحالة"));
    }
  };

  const createCoupon = async () => {
    if (!newCoupon.code || !newCoupon.code.trim()) {
      toast.error("الرجاء إدخال كود الكوبون");
      return;
    }
    
    if (!newCoupon.discount_percentage || newCoupon.discount_percentage <= 0 || newCoupon.discount_percentage > 100) {
      toast.error("الرجاء إدخال نسبة خصم صحيحة (1-100)");
      return;
    }
    
    try {
      const payload = {
        code: newCoupon.code,
        discount_percentage: parseFloat(newCoupon.discount_percentage),
        expiry_date: newCoupon.expiry_date ? `${newCoupon.expiry_date}T23:59:59` : null,
        description: newCoupon.description,
        min_purchase: newCoupon.min_purchase ? parseFloat(newCoupon.min_purchase) : 0
      };

      const response = await axios.post(`${API}/admin/coupons`, payload);
      toast.success(response.data.message || "تم إنشاء الكوبون بنجاح");
      fetchCoupons();
      setCreateCouponModal(false);
      
      setNewCoupon({ code: "", discount_percentage: "", expiry_date: "", description: "", min_purchase: "" });
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في إنشاء الكوبون"));
    }
  };

  const deleteCoupon = async (couponId) => {
    if (!window.confirm("هل أنت متأكد من حذف هذا الكوبون؟")) return;
    
    try {
      const response = await axios.delete(`${API}/admin/coupons/${couponId}`);
      toast.success(response.data.message || "تم حذف الكوبون");
      fetchCoupons();
    } catch (error) {
      toast.error(getErrorMessage(error, "فشل في حذف الكوبون"));
    }
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

  const filteredUsers = users.filter(u => 
    u.username?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    u.email?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const filteredOrders = orders.filter(o =>
    o.user_info?.username?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    o.prompt?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    o.coupon_code?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#F5F0E8] via-[#E8DCC8] to-[#F5F0E8]">
      <header className="glass border-b border-[#3E2723]/10 sticky top-0 z-50">
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
          <div className="flex gap-2 overflow-x-auto">
            {[
              { id: "overview", label: "نظرة عامة", shortLabel: "عامة", icon: TrendingUp },
              { id: "users", label: "المستخدمين", shortLabel: "المستخدمين", icon: Users },
              { id: "orders", label: "الطلبات", shortLabel: "الطلبات", icon: Package },
              { id: "designs", label: "التصاميم", shortLabel: "التصاميم", icon: Image },
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

        {activeTab === "overview" && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
                <div className="text-3xl font-bold text-[#3E2723]">{stats.total_revenue} ر.س</div>
              </CardContent>
            </Card>

            <Card className="glass border-[#D4AF37]/30">
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-[#5D4037]">إجمالي الخصومات</CardTitle>
                <Tag className="w-4 h-4 text-[#D4AF37]" />
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold text-[#3E2723]">{stats.total_discounts_given || 0} ر.س</div>
              </CardContent>
            </Card>
          </div>
        )}

        {activeTab === "users" && (
          <div>
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-bold text-[#3E2723]">إدارة المستخدمين</h2>
              <div className="relative">
                <Search className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5D4037]" />
                <Input
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="بحث..."
                  className="pr-10 w-64"
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
                      <th className="px-4 py-3 text-right text-sm font-semibold text-[#3E2723]">حد التصاميم</th>
                      <th className="px-4 py-3 text-right text-sm font-semibold text-[#3E2723]">تاريخ التسجيل</th>
                      <th className="px-4 py-3 text-right text-sm font-semibold text-[#3E2723]">إجراءات</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredUsers.map(u => (
                      <tr key={u.id} className="border-t border-[#3E2723]/10">
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
                            {u.is_unlimited ? 'غير محدود' : `${u.designs_used || 0}/${u.designs_limit}`}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-sm text-[#5D4037]">
                          {new Date(u.created_at).toLocaleDateString('ar-EG')}
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex gap-2">
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => setEditUserModal({ open: true, user: u })}
                              className="text-xs"
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
                    ))}
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
                        {u.is_unlimited ? 'غير محدود' : `${u.designs_used || 0}/${u.designs_limit}`}
                      </span>
                    </div>
                    
                    <div className="flex items-center justify-between text-xs text-[#5D4037] mb-3">
                      <span className="flex items-center gap-1">
                        <Calendar className="w-3 h-3" />
                        {new Date(u.created_at).toLocaleDateString('ar-EG')}
                      </span>
                    </div>
                    
                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => setEditUserModal({ open: true, user: u })}
                        className="text-xs flex-1"
                      >
                        <Edit className="w-3 h-3 ml-1" />
                        تعديل الحد
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
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        )}

        {activeTab === "orders" && (
          <div>
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
              <h2 className="text-xl sm:text-2xl font-bold text-[#3E2723]">إدارة الطلبات</h2>
             
              <Input
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="بحث..."
                className="w-full sm:w-64"
              />
            </div>
            
            <div className="grid gap-4">
              {filteredOrders.map(order => (
                <Card key={order.id} className="glass">
                  <CardContent className="p-3 sm:p-4">
                    <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                      <img 
                        src={`data:image/png;base64,${order.design_image_base64}`}
                        alt="Design"
                        className="w-full sm:w-24 h-40 sm:h-24 rounded-lg object-cover"
                      />
                      <div className="flex-1">
                        <div className="flex justify-between items-start mb-2">
                          <div>
                            <p className="font-semibold text-[#3E2723]">{order.user_name || order.user_info?.username}</p>
                            <p className="text-sm text-[#5D4037] line-clamp-2">{order.prompt}</p>
                          </div>
                          {getStatusBadge(order.status)}
                        </div>
                        <div className="flex flex-wrap gap-2 sm:gap-4 text-xs text-[#5D4037] mt-2">
                          <span className="flex items-center gap-1">
                            <Phone className="w-3 h-3" />
                            {order.phone_number}
                          </span>
                          <span className="flex items-center gap-1">
                            <Calendar className="w-3 h-3" />
                            {new Date(order.created_at).toLocaleDateString('ar-EG')}
                          </span>
                          {/* إضافة عرض الكوبون هنا */}
                          {order.coupon_code && (
                             <span className="flex items-center gap-1 font-semibold text-green-700 bg-green-100 border border-green-200 px-2 py-0.5 rounded-full">
                               <Tag className="w-3 h-3" />
                               كوبون: {order.coupon_code}
                             </span>
                          )}
                        </div>
                        
                        <div className="mt-3 sm:hidden">
                          <Select
                            value={order.status}
                            onValueChange={(val) => updateOrderStatus(order.id, val)}
                          >
                            <SelectTrigger className="w-full h-9 text-xs">
                              <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="pending">قيد الانتظار</SelectItem>
                              <SelectItem value="processing">قيد المعالجة</SelectItem>
                              <SelectItem value="completed">مكتمل</SelectItem>
                              <SelectItem value="cancelled">ملغي</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>
                      </div>
                      
                      <div className="hidden sm:flex flex-col gap-2">
                        <Select
                          value={order.status}
                          onValueChange={(val) => updateOrderStatus(order.id, val)}
                        >
                          <SelectTrigger className="w-32 h-8 text-xs">
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="pending">قيد الانتظار</SelectItem>
                            <SelectItem value="processing">قيد المعالجة</SelectItem>
                            <SelectItem value="completed">مكتمل</SelectItem>
                            <SelectItem value="cancelled">ملغي</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        )}

        {activeTab === "designs" && (
          <div>
            <h2 className="text-2xl font-bold text-[#3E2723] mb-6">جميع التصاميم</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {designs.map(design => (
                <Card key={design.id} className="glass overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
                  onClick={() => setViewDesignModal({ open: true, design })}>
                  <img 
                    src={`data:image/png;base64,${design.image_base64}`}
                    alt="Design"
                    className="w-full h-48 object-cover"
                  />
                  <CardContent className="p-4">
                    <p className="font-semibold text-[#3E2723] mb-1">{design.user_info?.username || design.user_name}</p>
                    <p className="text-sm text-[#5D4037] mb-2 line-clamp-2">{design.prompt}</p>
                    {design.phone_number && (
                      <p className="text-xs text-[#5D4037] flex items-center gap-1">
                        <Phone className="w-3 h-3" />
                        {design.phone_number}
                      </p>
                    )}
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        )}

        {activeTab === "showcase" && (
          <ShowcaseManager token={localStorage.getItem('token')} />
        )}

        {activeTab === "coupons" && (
          <div>
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-bold text-[#3E2723]">إدارة الكوبونات</h2>
              <Button onClick={() => setCreateCouponModal(true)} className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F]">
                <Plus className="w-4 h-4 ml-2" />
                إضافة كوبون
              </Button>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {coupons.map(coupon => (
                <Card key={coupon.id} className="glass">
                  <CardContent className="p-4">
                    <div className="flex justify-between items-start mb-3">
                      <div>
                        <p className="text-lg font-bold text-[#3E2723]">{coupon.code}</p>
                        <p className="text-sm text-[#5D4037]">خصم {coupon.discount_percentage}%</p>
                        {coupon.description && (
                          <p className="text-xs text-[#5D4037] mt-1">{coupon.description}</p>
                        )}
                      </div>
                      <div className="flex gap-1">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => fetchCouponUsage(coupon.id)}
                          className="text-blue-600 hover:text-blue-700"
                          title="عرض المستخدمين"
                        >
                          <Eye className="w-4 h-4" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => deleteCoupon(coupon.id)}
                          className="text-red-600 hover:text-red-700"
                        >
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </div>
                    </div>
                    
                    <div className="bg-[#D4AF37]/10 rounded-lg p-2 mb-2">
                      <div className="flex items-center justify-between text-sm mb-1">
                        <span className="text-[#5D4037]">الحد الأدنى للشراء:</span>
                        <span className="font-bold text-[#3E2723]">{coupon.min_purchase || 0} ر.س</span>
                      </div>
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-[#5D4037]">عدد الاستخدامات:</span>
                        <span className="font-bold text-[#3E2723]">
                          {coupon.current_uses || 0}
                          {coupon.max_uses ? ` / ${coupon.max_uses}` : ' (غير محدود)'}
                        </span>
                      </div>
                    </div>
                    
                    <div className="flex items-center justify-between text-xs text-[#5D4037]">
                      <span>
                        {coupon.is_active ? (
                          <span className="text-green-600">✓ فعال</span>
                        ) : (
                          <span className="text-red-600">✗ غير فعال</span>
                        )}
                      </span>
                      {coupon.expiry_date && (
                        <span>ينتهي: {new Date(coupon.expiry_date).toLocaleDateString('ar-EG')}</span>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        )}
      </div>

      <Dialog open={editUserModal.open} onOpenChange={(open) => setEditUserModal({ open, user: null })}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>تعديل حد التصاميم</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label>المستخدم: {editUserModal.user?.username}</Label>
              <p className="text-sm text-[#5D4037]">الحد الحالي: {editUserModal.user?.is_unlimited ? 'غير محدود' : editUserModal.user?.designs_limit}</p>
            </div>
            <div>
              <Label>الحد الجديد</Label>
              <Input 
                type="number" 
                id="newLimit"
                defaultValue={editUserModal.user?.is_unlimited ? -1 : editUserModal.user?.designs_limit}
                placeholder="أدخل الحد الجديد (-1 لغير محدود)"
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditUserModal({ open: false, user: null })}>
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
              className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F]"
            >
              حفظ
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={createCouponModal} onOpenChange={setCreateCouponModal}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>إنشاء كوبون جديد</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label>كود الكوبون</Label>
              <Input 
                value={newCoupon.code}
                onChange={(e) => setNewCoupon({...newCoupon, code: e.target.value})}
                placeholder="مثال: SUMMER2025" 
              />
            </div>
            <div>
              <Label>نسبة الخصم (%)</Label>
              <Input 
                type="number" 
                value={newCoupon.discount_percentage}
                onChange={(e) => setNewCoupon({...newCoupon, discount_percentage: e.target.value})}
                placeholder="20" 
              />
            </div>
            <div className="space-y-2">
              <Label>وصف الكوبون</Label>
              <Input 
                placeholder="مثلاً: خصم خاص بمناسبة عيد الفطر" 
                value={newCoupon.description}
                onChange={(e) => setNewCoupon({...newCoupon, description: e.target.value})}
              />
            </div>
            <div className="space-y-2">
              <Label>الحد الأدنى للشراء (ر.س)</Label>
              <Input 
                type="number"
                placeholder="0" 
                value={newCoupon.min_purchase}
                onChange={(e) => setNewCoupon({...newCoupon, min_purchase: e.target.value})}
              />
            </div>
            <div>
              <Label>تاريخ الانتهاء (اختياري)</Label>
              <Input 
                type="date" 
                value={newCoupon.expiry_date}
                onChange={(e) => setNewCoupon({...newCoupon, expiry_date: e.target.value})}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCreateCouponModal(false)}>
              إلغاء
            </Button>
            <Button 
              onClick={createCoupon}
              className="bg-gradient-to-l from-[#D4AF37] to-[#B8941F]"
            >
              إنشاء
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={viewDesignModal.open} onOpenChange={(open) => setViewDesignModal({ open, design: null })}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>تفاصيل التصميم</DialogTitle>
          </DialogHeader>
          {viewDesignModal.design && (
            <div className="space-y-4">
              <img 
                src={`data:image/png;base64,${viewDesignModal.design.image_base64}`}
                alt="Design"
                className="w-full rounded-lg"
              />
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p className="font-semibold text-[#3E2723]">المستخدم</p>
                  <p className="text-[#5D4037]">{viewDesignModal.design.user_info?.username || viewDesignModal.design.user_name}</p>
                </div>
                <div>
                  <p className="font-semibold text-[#3E2723]">البريد</p>
                  <p className="text-[#5D4037]">{viewDesignModal.design.user_info?.email || viewDesignModal.design.user_email}</p>
                </div>
                {viewDesignModal.design.phone_number && (
                  <div>
                    <p className="font-semibold text-[#3E2723]">رقم الهاتف</p>
                    <p className="text-[#5D4037]">{viewDesignModal.design.phone_number}</p>
                  </div>
                )}
                <div>
                  <p className="font-semibold text-[#3E2723]">التاريخ</p>
                  <p className="text-[#5D4037]">{new Date(viewDesignModal.design.created_at).toLocaleString('ar-EG')}</p>
                </div>
              </div>
              <div>
                <p className="font-semibold text-[#3E2723] mb-1">الوصف</p>
                <p className="text-[#5D4037]">{viewDesignModal.design.prompt}</p>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>

      <Dialog open={deleteUserModal.open} onOpenChange={(open) => setDeleteUserModal({ open, user: null })}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="text-red-600">⚠️ تأكيد حذف المستخدم</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
              <p className="text-red-800 font-semibold mb-2">
                هل أنت متأكد من حذف هذا المستخدم؟
              </p>
              <p className="text-red-700 text-sm">
                سيتم حذف المستخدم <strong>{deleteUserModal.user?.username}</strong> وجميع بياناته بشكل نهائي:
              </p>
              <ul className="text-red-700 text-sm mt-2 list-disc list-inside">
                <li>جميع التصاميم المحفوظة</li>
                <li>جميع الطلبات</li>
                <li>سجل استخدام الكوبونات</li>
              </ul>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteUserModal({ open: false, user: null })}>
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

      <Dialog open={couponUsageModal.open} onOpenChange={(open) => setCouponUsageModal({ open, coupon: null, usages: [] })}>
        <DialogContent className="max-w-3xl">
          <DialogHeader>
            <DialogTitle>
              إحصائيات الكوبون: {couponUsageModal.coupon?.coupon_code}
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div className="bg-[#D4AF37]/10 rounded-lg p-4">
              <div className="grid grid-cols-3 gap-4 text-center">
                <div>
                  <p className="text-2xl font-bold text-[#3E2723]">{couponUsageModal.coupon?.total_uses || 0}</p>
                  <p className="text-sm text-[#5D4037]">إجمالي الاستخدامات</p>
                </div>
                <div>
                  <p className="text-2xl font-bold text-[#3E2723]">{couponUsageModal.coupon?.discount_percentage}%</p>
                  <p className="text-sm text-[#5D4037]">نسبة الخصم</p>
                </div>
                <div>
                  <p className="text-2xl font-bold text-[#3E2723]">
                    {couponUsageModal.coupon?.max_uses || '∞'}
                  </p>
                  <p className="text-sm text-[#5D4037]">الحد الأقصى</p>
                </div>
              </div>
            </div>

            <div>
              <h4 className="font-semibold text-[#3E2723] mb-3">المستخدمون الذين استعملوا الكوبون:</h4>
              {couponUsageModal.usages.length === 0 ? (
                <p className="text-center text-[#5D4037] py-8">لم يتم استخدام هذا الكوبون بعد</p>
              ) : (
                <div className="max-h-64 overflow-y-auto">
                  <table className="w-full">
                    <thead className="bg-gray-50 sticky top-0">
                      <tr>
                        <th className="px-3 py-2 text-right text-xs font-semibold text-[#3E2723]">المستخدم</th>
                        <th className="px-3 py-2 text-right text-xs font-semibold text-[#3E2723]">البريد</th>
                        <th className="px-3 py-2 text-right text-xs font-semibold text-[#3E2723]">رقم الطلب</th>
                        <th className="px-3 py-2 text-right text-xs font-semibold text-[#3E2723]">قيمة الطلب</th>
                        <th className="px-3 py-2 text-right text-xs font-semibold text-[#3E2723]">تاريخ الاستخدام</th>
                      </tr>
                    </thead>
                    <tbody>
                      {couponUsageModal.usages.map((usage, index) => (
                        <tr key={usage.id || index} className="border-t">
                          <td className="px-3 py-2 text-sm text-[#3E2723]">{usage.username}</td>
                          <td className="px-3 py-2 text-sm text-[#5D4037]">{usage.email}</td>
                          <td className="px-3 py-2 text-sm text-[#5D4037]">{usage.order_id || 'N/A'}</td>
                          <td className="px-3 py-2 text-sm text-[#5D4037] font-bold">{usage.order_amount} ر.س</td>
                          <td className="px-3 py-2 text-sm text-[#5D4037]">
                            {new Date(usage.used_at).toLocaleString('ar-EG')}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}