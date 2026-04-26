import { useEffect, useState } from "react";
import { Link, NavLink, Outlet, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "@/context/AuthContext";
import http, { creditsShort } from "@/lib/api";
import {
  LayoutDashboard, Send, Upload, Calendar, Users, FileText, Wallet as WalletIcon,
  BarChart3, Key, LogOut, Bell, Search, Building2, ShieldCheck,
  History, Settings, IdCard, UserCog,
  Coins, TrendingUp, Gift, MessageCircle, Webhook, DollarSign, ChevronDown,
} from "lucide-react";

/* ---------- Grouped navigation ---------- */
const CLIENT_NAV = [
  {
    group: "Workspace",
    items: [
      { to: "/client/dashboard", icon: LayoutDashboard, label: "Dashboard" },
    ],
  },
  {
    group: "Send",
    items: [
      { to: "/client/quick-send",  icon: Send,     label: "Quick send" },
      { to: "/client/bulk-send",   icon: Upload,   label: "Bulk send" },
      { to: "/client/campaigns",   icon: Calendar, label: "Campaigns" },
    ],
  },
  {
    group: "Audience",
    items: [
      { to: "/client/contacts",       icon: Users,  label: "Contacts" },
      { to: "/client/number-lookup",  icon: Search, label: "Number lookup" },
    ],
  },
  {
    group: "Messaging assets",
    items: [
      { to: "/client/sender-ids", icon: IdCard,         label: "Sender IDs" },
      { to: "/client/templates",  icon: FileText,       label: "SMS templates" },
      { to: "/client/whatsapp",   icon: MessageCircle,  label: "WhatsApp" },
    ],
  },
  {
    group: "Reports & integration",
    items: [
      { to: "/client/reports",   icon: BarChart3, label: "Reports" },
      { to: "/client/api-keys",  icon: Key,       label: "API keys" },
      { to: "/client/webhooks",  icon: Webhook,   label: "Webhooks" },
    ],
  },
  {
    group: "Account",
    items: [
      { to: "/client/wallet",    icon: Coins, label: "Credits" },
      { to: "/client/referrals", icon: Gift,  label: "Referrals" },
    ],
  },
];

const RESELLER_NAV = [
  {
    group: "Workspace",
    items: [
      { to: "/reseller/dashboard", icon: LayoutDashboard, label: "Dashboard" },
    ],
  },
  {
    group: "Affiliate",
    items: [
      { to: "/reseller/codes",     icon: IdCard,     label: "Promo codes" },
      { to: "/reseller/referrals", icon: Users,      label: "Referrals" },
      { to: "/reseller/earnings",  icon: DollarSign, label: "Earnings" },
      { to: "/reseller/payouts",   icon: WalletIcon, label: "Payouts" },
    ],
  },
  {
    group: "Send",
    items: [
      { to: "/reseller/quick-send", icon: Send,     label: "Quick send" },
      { to: "/reseller/bulk-send",  icon: Upload,   label: "Bulk send" },
      { to: "/reseller/campaigns",  icon: Calendar, label: "Campaigns" },
    ],
  },
  {
    group: "Assets",
    items: [
      { to: "/reseller/contacts",   icon: Users,  label: "Contacts" },
      { to: "/reseller/sender-ids", icon: IdCard, label: "Sender IDs" },
    ],
  },
  {
    group: "Account",
    items: [
      { to: "/reseller/wallet",  icon: Coins,     label: "Credits" },
      { to: "/reseller/reports", icon: BarChart3, label: "Reports" },
    ],
  },
];

const ADMIN_NAV = [
  {
    group: "Platform ops",
    items: [
      { to: "/admin/users",        icon: UserCog,     label: "Users" },
      { to: "/admin/resellers",    icon: Users,       label: "Resellers" },
      { to: "/admin/institutions", icon: Building2,   label: "Institutions" },
      { to: "/admin/approvals",    icon: ShieldCheck, label: "Approvals" },
    ],
  },
  {
    group: "Insights",
    items: [
      { to: "/admin/overview", icon: LayoutDashboard, label: "Overview" },
      { to: "/admin/margin",   icon: TrendingUp,      label: "Margin & revenue" },
      { to: "/admin/audit",    icon: History,         label: "Audit logs" },
    ],
  },
  {
    group: "Configuration",
    items: [
      { to: "/admin/settings", icon: Settings, label: "Settings hub" },
    ],
  },
];

/* ---------- Sidebar primitives ---------- */
function NavItem({ to, icon: Icon, label }) {
  return (
    <NavLink
      to={to}
      end
      data-testid={`nav-${label.toLowerCase().replace(/\s+/g, "-")}`}
      className={({ isActive }) =>
        `group mx-2 flex items-center gap-3 rounded-md px-3 py-2 text-[13px] font-medium ` +
        `transition-[background-color,color] duration-150 ` +
        (isActive
          ? "bg-blue-500/10 text-zinc-100 ring-1 ring-inset ring-blue-500/25"
          : "text-zinc-400 hover:bg-white/[0.04] hover:text-zinc-100")
      }
    >
      <Icon className="h-[15px] w-[15px] shrink-0" strokeWidth={1.6} />
      <span className="truncate">{label}</span>
    </NavLink>
  );
}

function NavGroup({ label, children }) {
  return (
    <div className="pb-1.5">
      <div className="mb-1 mt-3 px-5 text-[10px] font-semibold uppercase tracking-[0.14em] text-zinc-600">
        {label}
      </div>
      <div className="flex flex-col gap-0.5">{children}</div>
    </div>
  );
}

function renderNav(navigation) {
  const isGrouped = navigation.length > 0 && navigation[0].group;
  if (!isGrouped) return navigation.map((n) => <NavItem key={n.to} {...n} />);
  return navigation.map((g) => (
    <NavGroup key={g.group} label={g.group}>
      {g.items.map((n) => <NavItem key={n.to} {...n} />)}
    </NavGroup>
  ));
}

/* ---------- Notification bell ---------- */
function NotificationBell() {
  const [open, setOpen] = useState(false);
  const [data, setData] = useState({ items: [], unread: 0 });

  const load = async () => {
    try { const r = await http.get("/notifications"); setData(r.data); }
    catch { /* noop */ }
  };

  useEffect(() => {
    load();
    const id = setInterval(load, 20000);
    return () => clearInterval(id);
  }, []);

  const markAll = async () => { await http.post("/notifications/read-all"); load(); };

  return (
    <div className="relative">
      <button
        onClick={() => setOpen((o) => !o)}
        data-testid="notification-bell"
        className="relative grid h-9 w-9 place-items-center rounded-md border border-zinc-800 bg-zinc-900/60 text-zinc-300 transition hover:border-zinc-700 hover:bg-zinc-800 hover:text-white"
      >
        <Bell className="h-4 w-4" strokeWidth={1.6} />
        {data.unread > 0 && (
          <span
            data-testid="notification-badge"
            className="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-[#0B0D10]"
          />
        )}
      </button>
      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute right-0 top-11 z-50 w-96 overflow-hidden rounded-xl border border-zinc-800 bg-[#14171C]/95 shadow-2xl shadow-black/50 backdrop-blur-xl">
            <div className="flex items-center justify-between border-b border-zinc-800/80 px-4 py-3">
              <div className="text-xs font-semibold text-zinc-300">
                Notifications {data.unread > 0 && <span className="ml-1.5 text-blue-400">· {data.unread} new</span>}
              </div>
              <button onClick={markAll} className="text-[11px] text-zinc-400 hover:text-white" data-testid="notif-mark-all">
                Mark all read
              </button>
            </div>
            <div className="max-h-96 overflow-y-auto">
              {data.items.length === 0 && (
                <div className="p-6 text-center text-sm text-zinc-500">All clear.</div>
              )}
              {data.items.map((n) => (
                <div key={n.id} className="border-b border-zinc-800/60 px-4 py-3 hover:bg-white/[0.02]">
                  <div className="flex items-start gap-3">
                    <span
                      className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${
                        n.kind === "success" ? "bg-emerald-500" :
                        n.kind === "warning" ? "bg-amber-500" :
                        n.kind === "error"   ? "bg-red-500" : "bg-blue-500"
                      } ${!n.read ? "ring-2 ring-blue-500/25" : ""}`}
                    />
                    <div className="flex-1 min-w-0">
                      <div className="text-sm font-medium text-zinc-100">{n.title}</div>
                      <div className="mt-0.5 text-xs leading-relaxed text-zinc-400">{n.body}</div>
                      <div className="mt-1 text-[10px] uppercase tracking-wider text-zinc-600">
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

/* ---------- Shell ---------- */
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
    user.role === "super_admin"   ? "Admin console"     :
    user.role === "country_admin" ? "Country admin"     :
    user.role === "reseller"      ? "Reseller portal"   : "Client workspace";

  return (
    <div className="flex min-h-screen bg-[#0B0D10] text-zinc-100">
      {/* SIDEBAR */}
      <aside className="hidden w-64 shrink-0 flex-col border-r border-zinc-800/80 bg-[#0E1014] lg:flex">
        {/* Brand */}
        <div className="flex h-14 items-center gap-2.5 border-b border-zinc-800/80 px-5">
          <Link to="/" className="flex items-center gap-2.5" data-testid="shell-brand">
            <div className="grid h-7 w-7 place-items-center rounded-md bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm shadow-blue-500/20">
              <span className="font-display text-[13px] font-bold leading-none">u</span>
            </div>
            <span className="font-display text-base font-semibold tracking-tight">unitxt</span>
          </Link>
        </div>

        {/* Workspace tag */}
        <div className="border-b border-zinc-800/80 px-5 py-3.5">
          <div className="text-[10px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
            {roleLabel}
          </div>
          <div className="mt-1 truncate text-sm font-medium text-zinc-100">
            {user.business_name || user.name}
          </div>
        </div>

        <nav className="flex-1 overflow-y-auto py-2">{renderNav(navigation)}</nav>

        {/* Sign out */}
        <div className="border-t border-zinc-800/80 p-3">
          <button
            onClick={async () => { await logout(); nav("/"); }}
            data-testid="logout-btn"
            className="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-sm text-zinc-400 transition hover:bg-white/[0.04] hover:text-white"
          >
            <LogOut className="h-4 w-4" strokeWidth={1.6} /> Sign out
          </button>
        </div>
      </aside>

      {/* MAIN */}
      <div className="flex min-w-0 flex-1 flex-col">
        {/* TOPBAR */}
        <header className="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-zinc-800/80 bg-[#0B0D10]/85 px-6 backdrop-blur-xl">
          <div className="flex items-center gap-3">
            <div className="hidden items-center gap-2 rounded-md border border-zinc-800 bg-zinc-900/50 px-3 py-1.5 text-zinc-400 transition focus-within:border-blue-500/60 focus-within:ring-2 focus-within:ring-blue-500/15 lg:flex">
              <Search className="h-3.5 w-3.5" strokeWidth={1.8} />
              <input
                placeholder="Search"
                data-testid="topbar-search"
                className="w-48 bg-transparent text-xs text-zinc-200 outline-none placeholder:text-zinc-500"
              />
              <span className="rounded border border-zinc-700/70 bg-zinc-800/50 px-1.5 py-0.5 font-mono text-[10px] text-zinc-500">⌘K</span>
            </div>
          </div>

          <div className="flex items-center gap-2.5">
            {user.role !== "super_admin" && wallet && (
              <div
                className="hidden items-center gap-2.5 rounded-md border border-zinc-800 bg-zinc-900/50 px-3 py-1.5 sm:flex"
                data-testid="topbar-wallet"
              >
                <WalletIcon className="h-3.5 w-3.5 text-blue-400" strokeWidth={1.8} />
                <div className="font-mono text-sm font-medium text-zinc-100">{creditsShort(wallet.balance)}</div>
                <span className="text-[10px] uppercase tracking-wider text-zinc-500">credits</span>
              </div>
            )}

            <NotificationBell />

            <div className="flex items-center gap-2.5 rounded-full border border-zinc-800 bg-zinc-900/50 py-1 pl-1.5 pr-3 transition hover:border-zinc-700">
              <div className="grid h-7 w-7 place-items-center rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-[11px] font-semibold text-white shadow-sm shadow-blue-500/20">
                {user.name?.charAt(0).toUpperCase() || "U"}
              </div>
              <div className="hidden sm:block">
                <div className="text-[13px] font-medium leading-tight text-zinc-100">{user.name}</div>
                <div className="text-[10px] capitalize tracking-wide text-zinc-500">{user.role.replace("_", " ")}</div>
              </div>
              <ChevronDown className="hidden h-3 w-3 text-zinc-500 sm:block" strokeWidth={2} />
            </div>
          </div>
        </header>

        {/* PAGE */}
        <main className="flex-1 overflow-y-auto">
          <div className="rise mx-auto max-w-[1400px] px-6 py-7">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
