import "@/App.css";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { Toaster } from "sonner";
import { AuthProvider, useAuth } from "@/context/AuthContext";
import Landing from "@/pages/Landing";
import Login from "@/pages/Login";
import Register from "@/pages/Register";
import AppShell from "@/components/AppShell";

import ClientDashboard from "@/pages/client/ClientDashboard";
import QuickSend from "@/pages/client/QuickSend";
import BulkSend from "@/pages/client/BulkSend";
import Campaigns from "@/pages/client/Campaigns";
import Contacts from "@/pages/client/Contacts";
import SenderIds from "@/pages/client/SenderIds";
import Templates from "@/pages/client/Templates";
import Wallet from "@/pages/client/Wallet";
import Reports from "@/pages/client/Reports";
import ApiKeys from "@/pages/client/ApiKeys";

import ResellerDashboard from "@/pages/reseller/ResellerDashboard";
import ResellerClients from "@/pages/reseller/ResellerClients";
import ResellerEarnings from "@/pages/reseller/ResellerEarnings";

import AdminCreditPacks from "@/pages/admin/AdminCreditPacks";
import AdminMobilePrefixes from "@/pages/admin/AdminMobilePrefixes";
import AdminMargin from "@/pages/admin/AdminMargin";
import AdminWhatsApp from "@/pages/admin/AdminWhatsApp";
import Referrals from "@/pages/client/Referrals";
import WhatsAppTemplates from "@/pages/client/WhatsAppTemplates";
import WebhookSettings from "@/pages/client/WebhookSettings";
import ResellerPricing from "@/pages/reseller/ResellerPricing";

import AdminOverview from "@/pages/admin/AdminOverview";
import AdminUsers from "@/pages/admin/AdminUsers";
import AdminProviders from "@/pages/admin/AdminProviders";
import AdminCountries from "@/pages/admin/AdminCountries";
import AdminPricing from "@/pages/admin/AdminPricing";
import AdminSenderIds from "@/pages/admin/AdminSenderIds";
import AdminWallets from "@/pages/admin/AdminWallets";
import AdminCampaigns from "@/pages/admin/AdminCampaigns";
import AdminInstitutions from "@/pages/admin/AdminInstitutions";
import AdminPromotions from "@/pages/admin/AdminPromotions";
import AdminResellers from "@/pages/admin/AdminResellers";
import CountryHub from "@/pages/admin/CountryHub";
import CountryDetail from "@/pages/admin/CountryDetail";
import IntegrationHealth from "@/pages/admin/IntegrationHealth";
import AdminApprovals from "@/pages/admin/AdminApprovals";
import ApplyPage from "@/pages/ApplyPage";
import NumberLookup from "@/pages/client/NumberLookup";
import AdminAudit from "@/pages/admin/AdminAudit";
import SettingsHub from "@/pages/admin/SettingsHub";
import RoutingEngine from "@/pages/admin/RoutingEngine";

function ProtectedRoute({ children, roles }) {
  const { user } = useAuth();
  if (user === null)
    return (
      <div className="flex h-screen items-center justify-center text-zinc-500" data-testid="auth-loader">
        <div className="font-mono text-xs tracking-widest">LOADING…</div>
      </div>
    );
  if (user === false) return <Navigate to="/login" replace />;
  if (roles && !roles.includes(user.role)) return <Navigate to="/app" replace />;
  return children;
}

function RoleHome() {
  const { user } = useAuth();
  if (!user) return null;
  if (user.role === "super_admin" || user.role === "country_admin")
    return <Navigate to="/admin/overview" replace />;
  if (user.role === "reseller") return <Navigate to="/reseller/dashboard" replace />;
  return <Navigate to="/client/dashboard" replace />;
}

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Toaster
          theme="dark"
          position="top-right"
          toastOptions={{
            style: {
              background: "#141414",
              border: "1px solid #27272A",
              color: "#fff",
              borderRadius: "4px",
            },
          }}
        />
        <Routes>
          <Route path="/" element={<Landing />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/apply/:kind" element={<ApplyPage />} />

          <Route path="/app" element={<ProtectedRoute><RoleHome /></ProtectedRoute>} />

          {/* Client */}
          <Route path="/client" element={<ProtectedRoute roles={["client"]}><AppShell /></ProtectedRoute>}>
            <Route path="dashboard" element={<ClientDashboard />} />
            <Route path="quick-send" element={<QuickSend />} />
            <Route path="bulk-send" element={<BulkSend />} />
            <Route path="campaigns" element={<Campaigns />} />
            <Route path="contacts" element={<Contacts />} />
            <Route path="number-lookup" element={<NumberLookup />} />
            <Route path="sender-ids" element={<SenderIds />} />
            <Route path="templates" element={<Templates />} />
            <Route path="wallet" element={<Wallet />} />
            <Route path="reports" element={<Reports />} />
            <Route path="api-keys" element={<ApiKeys />} />
            <Route path="referrals" element={<Referrals />} />
            <Route path="whatsapp" element={<WhatsAppTemplates />} />
            <Route path="webhooks" element={<WebhookSettings />} />
          </Route>

          {/* Reseller */}
          <Route path="/reseller" element={<ProtectedRoute roles={["reseller"]}><AppShell /></ProtectedRoute>}>
            <Route path="dashboard" element={<ResellerDashboard />} />
            <Route path="clients" element={<ResellerClients />} />
            <Route path="earnings" element={<ResellerEarnings />} />
            <Route path="wallet" element={<Wallet />} />
            <Route path="campaigns" element={<Campaigns />} />
            <Route path="quick-send" element={<QuickSend />} />
            <Route path="bulk-send" element={<BulkSend />} />
            <Route path="sender-ids" element={<SenderIds />} />
            <Route path="contacts" element={<Contacts />} />
            <Route path="reports" element={<Reports />} />
            <Route path="pricing" element={<ResellerPricing />} />
            <Route path="referrals" element={<Referrals />} />
          </Route>

          {/* Admin */}
          <Route path="/admin" element={<ProtectedRoute roles={["super_admin", "country_admin"]}><AppShell /></ProtectedRoute>}>
            <Route path="overview" element={<AdminOverview />} />
            <Route path="users" element={<AdminUsers />} />
            <Route path="providers" element={<AdminProviders />} />
            <Route path="countries" element={<AdminCountries />} />
            <Route path="pricing" element={<AdminPricing />} />
            <Route path="sender-ids" element={<AdminSenderIds />} />
            <Route path="wallets" element={<AdminWallets />} />
            <Route path="campaigns" element={<AdminCampaigns />} />
            <Route path="institutions" element={<AdminInstitutions />} />
            <Route path="promotions" element={<AdminPromotions />} />
            <Route path="audit" element={<AdminAudit />} />
            <Route path="settings" element={<SettingsHub />} />
            <Route path="routing" element={<RoutingEngine />} />
            <Route path="credit-packs" element={<AdminCreditPacks />} />
            <Route path="prefixes" element={<AdminMobilePrefixes />} />
            <Route path="margin" element={<AdminMargin />} />
            <Route path="whatsapp" element={<AdminWhatsApp />} />
            <Route path="resellers" element={<AdminResellers />} />
            <Route path="country-hub" element={<CountryHub />} />
            <Route path="country-hub/:code" element={<CountryDetail />} />
            <Route path="integrations" element={<IntegrationHealth />} />
            <Route path="approvals" element={<AdminApprovals />} />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
