import { useEffect, useState } from "react";
import { Link, NavLink, Outlet, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "@/context/AuthContext";
import http, { money, creditsShort } from "@/lib/api";
import {
  LayoutDashboard, Send, Upload, Calendar, Users, Tag, FileText, Wallet as WalletIcon,
  BarChart3, Key, LogOut, Bell, Search, Globe2, Building2, DollarSign, ShieldCheck,
  ListChecks, Megaphone, Receipt, History, Settings, Network, IdCard, UserCog,
  Coins, Smartphone, TrendingUp, Gift, MessageCircle, Webhook,
} from "lucide-react";

const CLIENT_NAV = [
  { to: "/client/dashboard", icon: LayoutDashboard, label: "Dashboard" },
  { to: "/client/quick-send", icon: Send, label: "Quick send" },
  { to: "/client/bulk-send", icon: Upload, label: "Bulk send" },
  { to: "/client/campaigns", icon: Calendar, label: "Campaigns" },
  { to: "/client/contacts", icon: Users, label: "Contacts" },
  { to: "/client/sender-ids", icon: IdCard, label: "Sender IDs" },
  { to: "/client/whatsapp", icon: MessageCircle, label: "WhatsApp" },
  { to: "/client/templates", icon: FileText, label: "SMS templates" },
  { to: "/client/wallet", icon: Coins, label: "Credits" },
  { to: "/client/referrals", icon: Gift, label: "Referrals" },
  { to: "/client/reports", icon: BarChart3, label: "Reports" },
  { to: "/client/api-keys", icon: Key, label: "API keys" },
  { to: "/client/webhooks", icon: Webhook, label: "Webhooks" },
];
const RESELLER_NAV = [
  { to: "/reseller/dashboard", icon: LayoutDashboard, label: "Dashboard" },
  { to: "/reseller/clients", icon: Users, label: "Clients" },
  { to: "/reseller/pricing", icon: DollarSign, label: "Client pricing" },
  { to: "/reseller/earnings", icon: DollarSign, label: "Earnings" },
  { to: "/reseller/referrals", icon: Gift, label: "Referrals" },
  { to: "/reseller/wallet", icon: Coins, label: "Float credits" },
  { to: "/reseller/quick-send", icon: Send, label: "Quick send" },
  { to: "/reseller/bulk-send", icon: Upload, label: "Bulk send" },
  { to: "/reseller/campaigns", icon: Calendar, label: "Campaigns" },
  { to: "/reseller/sender-ids", icon: IdCard, label: "Sender IDs" },
  { to: "/reseller/contacts", icon: Tag, label: "Contacts" },
  { to: "/reseller/reports", icon: BarChart3, label: "Reports" },
];
const ADMIN_NAV = [
  { to: "/admin/overview", icon: LayoutDashboard, label: "Overview" },
  { to: "/admin/margin", icon: TrendingUp, label: "Margin & revenue" },
  { to: "/admin/users", icon: UserCog, label: "Users" },
  { to: "/admin/providers", icon: Network, label: "Providers" },
  { to: "/admin/routing", icon: Globe2, label: "Routing engine" },
  { to: "/admin/countries", icon: Globe2, label: "Countries" },
  { to: "/admin/prefixes", icon: Smartphone, label: "Mobile prefixes" },
  { to: "/admin/pricing", icon: DollarSign, label: "Pricing" },
  { to: "/admin/credit-packs", icon: Coins, label: "Credit packs" },
  { to: "/admin/sender-ids", icon: ListChecks, label: "Sender IDs" },
  { to: "/admin/wallets", icon: WalletIcon, label: "Wallets" },
  { to: "/admin/campaigns", icon: Calendar, label: "Campaigns" },
  { to: "/admin/institutions", icon: Building2, label: "Institutions" },
  { to: "/admin/promotions", icon: Megaphone, label: "Promotions" },
  { to: "/admin/country-hub", icon: Globe2, label: "Country hub" },
  { to: "/admin/integrations", icon: Network, label: "Integration health" },
  { to: "/admin/resellers", icon: Users, label: "Resellers" },
  { to: "/admin/whatsapp", icon: MessageCircle, label: "WhatsApp approvals" },
  { to: "/admin/audit", icon: History, label: "Audit logs" },
  { to: "/admin/settings", icon: Settings, label: "Settings hub" },
];

function NavItem({ to, icon: Icon, label }) {
  return (
    <NavLink
      to={to}
      end
      data-testid={`nav-${label.toLowerCase().replace(/\s+/g, "-")}`}
      className={({ isActive }) =>
        `group flex items-center gap-3 px-4 py-2 text-sm transition-all duration-150 border-l-2 ${
          isActive
            ? "tracing-beam-active text-white"
            : "border-transparent text-zinc-500 hover:text-white hover:bg-white/5"
        }`
      }
    >
      <Icon className="h-4 w-4" strokeWidth={1.5} />
      <span>{label}</span>
    </NavLink>
  );
}

function NotificationBell() {
  const [open, setOpen] = useState(false);
  const [data, setData] = useState({ items: [], unread: 0 });

  const load = async () => {
    try {
      const r = await http.get("/notifications");
      setData(r.data);
    } catch { /* noop */ }
  };

  useEffect(() => {
    load();
    const id = setInterval(load, 20000);
    return () => clearInterval(id);
  }, []);

  const markAll = async () => {
    await http.post("/notifications/read-all");
    load();
  };

  return (
    <div className="relative">
      <button
        onClick={() => setOpen((o) => !o)}
        data-testid="notification-bell"
        className="relative grid h-9 w-9 place-items-center border border-zinc-800 bg-zinc-950 text-zinc-300 transition hover:border-zinc-600 hover:text-white"
      >
        <Bell className="h-4 w-4" strokeWidth={1.5} />
        {data.unread > 0 && (
          <span
            data-testid="notification-badge"
            className="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-red-500 animate-pulse shadow-[0_0_8px_rgba(255,59,48,0.6)]"
          />
        )}
      </button>
      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute right-0 top-11 z-50 w-96 border border-zinc-800 bg-[#141414]/95 backdrop-blur-xl">
            <div className="flex items-center justify-between border-b border-zinc-900 p-3">
              <div className="label-overline">Notifications · {data.unread} new</div>
              <button onClick={markAll} className="text-[11px] text-zinc-400 hover:text-white" data-testid="notif-mark-all">
                Mark all read
              </button>
            </div>
            <div className="max-h-96 overflow-y-auto">
              {data.items.length === 0 && (
                <div className="p-6 text-center text-sm text-zinc-500">All clear.</div>
              )}
              {data.items.map((n) => (
                <div key={n.id} className="border-b border-zinc-900 px-4 py-3 hover:bg-white/[0.02]">
                  <div className="flex items-start gap-3">
                    <span
                      className={`mt-1 h-2 w-2 rounded-full ${
                        n.kind === "success" ? "bg-emerald-500" :
                        n.kind === "warning" ? "bg-orange-500" :
                        n.kind === "error" ? "bg-red-500" : "bg-blue-500"
                      } ${!n.read ? "ring-2 ring-white/20" : ""}`}
                    />
                    <div className="flex-1">
                      <div className="text-sm font-medium text-white">{n.title}</div>
                      <div className="mt-0.5 text-xs text-zinc-500">{n.body}</div>
                      <div className="mt-1 font-mono text-[10px] uppercase tracking-widest text-zinc-600">
                        {new Date(n.created_at).toLocaleString()}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </>
      )}
    </div>
  );
}

export default function AppShell() {
  const { user, wallet, logout, refresh } = useAuth();
  const nav = useNavigate();
  const loc = useLocation();

  useEffect(() => { refresh(); /* eslint-disable-next-line */ }, [loc.pathname]);

  if (!user) return null;
  const navigation =
    user.role === "super_admin" || user.role === "country_admin" ? ADMIN_NAV
    : user.role === "reseller" ? RESELLER_NAV
    : CLIENT_NAV;

  const roleLabel =
    user.role === "super_admin" ? "ADMIN CONSOLE" :
    user.role === "country_admin" ? "COUNTRY ADMIN" :
    user.role === "reseller" ? "RESELLER PORTAL" : "CLIENT WORKSPACE";

  return (
    <div className="flex min-h-screen bg-[#0A0A0A] text-white">
      {/* SIDEBAR */}
      <aside className="hidden w-64 shrink-0 flex-col border-r border-zinc-900 lg:flex">
        <div className="flex h-14 items-center gap-2 border-b border-zinc-900 px-5">
          <Link to="/" className="flex items-center gap-2" data-testid="shell-brand">
            <div className="grid h-7 w-7 place-items-center bg-white text-black">
              <span className="font-display text-sm font-bold">u</span>
            </div>
            <span className="font-display text-base font-bold">unitxt</span>
          </Link>
        </div>
        <div className="px-5 py-3 border-b border-zinc-900">
          <div className="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-500">{roleLabel}</div>
          <div className="mt-1 truncate text-sm font-medium">{user.business_name || user.name}</div>
        </div>
        <nav className="flex-1 overflow-y-auto py-3">
          {navigation.map((n) => <NavItem key={n.to} {...n} />)}
        </nav>
        <div className="border-t border-zinc-900 p-4">
          <button
            onClick={async () => { await logout(); nav("/"); }}
            data-testid="logout-btn"
            className="flex w-full items-center gap-2 text-sm text-zinc-400 hover:text-white"
          >
            <LogOut className="h-4 w-4" /> Sign out
          </button>
        </div>
      </aside>

      {/* MAIN */}
      <div className="flex min-w-0 flex-1 flex-col">
        {/* TOPBAR */}
        <header className="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-zinc-900 bg-[#0A0A0A]/80 px-6 backdrop-blur-xl">
          <div className="flex items-center gap-3">
            <div className="hidden items-center gap-2 border border-zinc-800 px-3 py-1.5 text-zinc-500 lg:flex">
              <Search className="h-3.5 w-3.5" />
              <input
                placeholder="Search"
                data-testid="topbar-search"
                className="w-48 bg-transparent text-xs text-zinc-300 outline-none placeholder:text-zinc-600"
              />
              <span className="font-mono text-[10px] text-zinc-600">⌘K</span>
            </div>
          </div>
          <div className="flex items-center gap-3">
            {user.role !== "super_admin" && wallet && (
              <div className="hidden items-center gap-3 border border-zinc-800 bg-zinc-950 px-3 py-1.5 sm:flex" data-testid="topbar-wallet">
                <WalletIcon className="h-3.5 w-3.5 text-emerald-400" />
                <div className="font-mono text-sm font-medium">{creditsShort(wallet.balance)}</div>
                <span className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">credits</span>
              </div>
            )}
            <NotificationBell />
            <div className="flex items-center gap-3 border border-zinc-800 bg-zinc-950 px-3 py-1.5">
              <div className="grid h-6 w-6 place-items-center rounded-full bg-white text-[10px] font-bold text-black">
                {user.name?.charAt(0).toUpperCase() || "U"}
              </div>
              <div className="hidden text-xs sm:block">
                <div className="font-medium text-white">{user.name}</div>
                <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{user.role.replace("_"," ")}</div>
              </div>
            </div>
          </div>
        </header>

        {/* PAGE */}
        <main className="flex-1 overflow-y-auto">
          <div className="rise mx-auto max-w-[1400px] px-6 py-6">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
