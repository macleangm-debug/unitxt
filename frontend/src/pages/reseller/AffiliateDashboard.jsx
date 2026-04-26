import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import http, { money, shortDate } from "@/lib/api";
import { useAuth } from "@/context/AuthContext";
import { PageHeader, Card, Btn, Pill } from "@/components/UI";
import {
  Tag, Users, DollarSign, Wallet as WalletIcon,
  ArrowUpRight, Sparkles, TrendingUp, Copy, CheckCircle2,
} from "lucide-react";

export default function AffiliateDashboard() {
  const { user } = useAuth();
  const [me, setMe] = useState(null);
  const [recent, setRecent] = useState([]);
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    Promise.all([
      http.get("/affiliate/me"),
      http.get("/affiliate/earnings"),
    ]).then(([m, e]) => { setMe(m.data); setRecent((e.data || []).slice(0, 5)); }).catch(() => {});
  }, []);

  if (!me) return <div className="text-zinc-500">Loading…</div>;
  const cfg = me.config || {};

  const shareLink = me.primary_code
    ? `${window.location.origin}/register?ref=${me.primary_code}`
    : null;

  const copyShare = () => {
    if (!shareLink) return;
    navigator.clipboard?.writeText(shareLink);
    setCopied(true); setTimeout(() => setCopied(false), 1500);
  };

  return (
    <div>
      <PageHeader
        overline="Affiliate workspace"
        title={`Hi, ${user?.name?.split(" ")[0] || "there"}.`}
        desc="Share your promo code, earn commission on every paid top-up your referrals make."
      />

      {/* HERO — primary code + share link */}
      <Card className="mb-6 overflow-hidden bg-gradient-to-br from-blue-500/[0.06] via-transparent to-transparent" testid="aff-hero">
        <div className="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
          <div className="min-w-0">
            <div className="label-overline flex items-center gap-1.5 text-blue-400">
              <Tag className="h-3 w-3"/> Your primary promo code
            </div>
            <div className="mt-2 flex items-baseline gap-3">
              <span className="font-mono text-4xl font-medium tracking-tight text-zinc-100" data-testid="aff-primary-code">
                {me.primary_code || "—"}
              </span>
              {me.primary_code_renamed
                ? <Pill status="success">customised</Pill>
                : <Pill status="info">unitxt default</Pill>}
            </div>
            {!me.primary_code_renamed && (
              <div className="mt-3 inline-flex items-center gap-1.5 rounded-md border border-amber-500/25 bg-amber-500/[0.06] px-2.5 py-1 text-[11px] text-amber-300">
                <Sparkles className="h-3 w-3"/>
                You can change this once. Make it memorable —{" "}
                <Link to="/reseller/codes" className="font-medium underline-offset-2 hover:underline" data-testid="aff-customize-link">
                  customise it →
                </Link>
              </div>
            )}
          </div>

          <div className="min-w-0">
            <div className="label-overline mb-1.5">Share link</div>
            <div className="flex items-center gap-2 rounded-md border border-zinc-800 bg-zinc-950/60 p-2.5">
              <code className="flex-1 truncate font-mono text-xs text-zinc-300">{shareLink || "—"}</code>
              <button
                onClick={copyShare}
                disabled={!shareLink}
                data-testid="aff-copy-link"
                className="inline-flex shrink-0 items-center gap-1 rounded px-2 py-1 text-[11px] text-zinc-300 transition hover:bg-white/5 disabled:opacity-40"
              >
                {copied ? <CheckCircle2 className="h-3 w-3 text-emerald-400"/> : <Copy className="h-3 w-3"/>}
                {copied ? "Copied" : "Copy"}
              </button>
            </div>
            <p className="mt-1.5 text-[11px] text-zinc-500">
              Send this link. New users land pre-filled with your code.
            </p>
          </div>
        </div>
      </Card>

      {/* STAT GRID */}
      <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Stat icon={Users}      label="Referrals"       value={me.referrals}                    testid="aff-stat-referrals"/>
        <Stat icon={DollarSign} label="Earned"          value={money(me.earned_usd)}    sub={fmtLocal(me.earned_local, me.local_currency)}    testid="aff-stat-earned"  green/>
        <Stat icon={WalletIcon} label="In review"       value={money(me.requested_usd)} sub={fmtLocal(me.requested_local, me.local_currency)} testid="aff-stat-requested"/>
        <Stat icon={CheckCircle2} label="Paid out"      value={money(me.paid_usd)}      sub={fmtLocal(me.paid_local, me.local_currency)}     testid="aff-stat-paid" green/>
      </div>

      {/* COMMISSION RULES BANNER */}
      <Card className="mb-6 border-blue-500/20 bg-blue-500/[0.04]" testid="aff-rules">
        <div className="flex items-start gap-3">
          <TrendingUp className="mt-0.5 h-4 w-4 shrink-0 text-blue-400" strokeWidth={1.8} />
          <div>
            <div className="text-sm font-medium text-zinc-100">How you earn</div>
            <p className="mt-1 text-sm leading-relaxed text-zinc-400">
              {cfg.model === "time_window" && (
                <>You earn <strong className="text-zinc-200">{cfg.commission_pct}% of every paid top-up</strong> made by your referrals,{" "}
                  for the first <strong className="text-zinc-200">{cfg.window_months} months</strong> after they sign up.</>
              )}
              {cfg.model === "first_n" && (
                <>You earn <strong className="text-zinc-200">{cfg.commission_pct}%</strong> on each of your referral's first{" "}
                  <strong className="text-zinc-200">{cfg.first_n} paid top-ups</strong>. After that, the commission stops.</>
              )}
              {cfg.model === "tier_bonus" && (
                <>You earn <strong className="text-zinc-200">{cfg.tier_first_pct}% of your referral's first paid top-up</strong>,{" "}
                  + <strong className="text-zinc-200">${cfg.tier_bonus_1_amount_usd}</strong> when they spend ${cfg.tier_bonus_1_threshold_usd}+,{" "}
                  + <strong className="text-zinc-200">${cfg.tier_bonus_2_amount_usd}</strong> when they spend ${cfg.tier_bonus_2_threshold_usd}+ within {cfg.tier_window_months} months.</>
              )}
              {" "}Cash out anytime above <strong className="text-zinc-200">${cfg.payout_threshold_usd}</strong>.
            </p>
          </div>
        </div>
      </Card>

      {/* RECENT EARNINGS + QUICK LINKS */}
      <div className="grid gap-5 lg:grid-cols-[1.4fr,minmax(0,1fr)]">
        <Card>
          <div className="mb-3 flex items-center justify-between">
            <div className="label-overline">Recent earnings</div>
            <Link to="/reseller/earnings" className="text-xs text-blue-400 hover:text-blue-300" data-testid="aff-all-earnings">
              View all →
            </Link>
          </div>
          {recent.length === 0 ? (
            <div className="rounded-md border border-dashed border-zinc-800 px-6 py-8 text-center text-sm text-zinc-500">
              No commissions yet. Share your code and the moment a referral makes a paid top-up, you'll see it here.
            </div>
          ) : (
            <div className="divide-y divide-zinc-800/60">
              {recent.map((e) => (
                <div key={e.id} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <div className="truncate text-sm text-zinc-100">{e.referred_email || "—"}</div>
                    <div className="text-[11px] text-zinc-500">
                      {shortDate(e.created_at)} · {labelKind(e.kind)}
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-sm font-medium text-emerald-400">+{money(e.amount_usd)}</span>
                    <Pill status={e.status === "earned" ? "success" : e.status === "requested" ? "pending" : "info"}>
                      {e.status}
                    </Pill>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>

        <Card>
          <div className="label-overline mb-3">Quick actions</div>
          <div className="grid gap-2">
            <QuickLink to="/reseller/codes" icon={Tag} label="Promo codes" desc="Edit primary, mint extras"/>
            <QuickLink to="/reseller/referrals" icon={Users} label="Referrals" desc="See who you've brought in"/>
            <QuickLink to="/reseller/earnings" icon={DollarSign} label="Earnings ledger" desc="Every commission you've earned"/>
            <QuickLink to="/reseller/payouts" icon={WalletIcon} label="Payouts" desc={`Cash out (min $${cfg.payout_threshold_usd})`}/>
          </div>
        </Card>
      </div>
    </div>
  );
}

function Stat({ icon: Icon, label, value, sub, green, testid }) {
  return (
    <div className="card-surface p-4" data-testid={testid}>
      <div className="flex items-center gap-1.5 text-zinc-500">
        <Icon className="h-3.5 w-3.5 text-blue-400" strokeWidth={1.8}/>
        <span className="text-[10px] font-semibold uppercase tracking-wider">{label}</span>
      </div>
      <div className={`mt-2 font-mono text-xl font-medium ${green ? "text-emerald-400" : "text-zinc-100"}`}>
        {value}
      </div>
      {sub && <div className="mt-0.5 font-mono text-[10px] text-zinc-500">{sub}</div>}
    </div>
  );
}

function fmtLocal(amount, currency) {
  if (amount == null || !currency || currency === "USD") return null;
  return `≈ ${currency} ${Number(amount).toLocaleString()}`;
}

function QuickLink({ to, icon: Icon, label, desc }) {
  return (
    <Link
      to={to}
      className="group flex items-center justify-between rounded-md border border-zinc-800/80 bg-zinc-950/30 p-3 transition hover:border-blue-500/30 hover:bg-blue-500/[0.04]"
    >
      <div className="flex items-center gap-3 min-w-0">
        <div className="grid h-8 w-8 shrink-0 place-items-center rounded-md bg-zinc-900/60 text-zinc-300 group-hover:bg-blue-500/20 group-hover:text-blue-300">
          <Icon className="h-4 w-4" strokeWidth={1.8}/>
        </div>
        <div className="min-w-0">
          <div className="truncate text-sm font-medium text-zinc-100">{label}</div>
          <div className="truncate text-[11px] text-zinc-500">{desc}</div>
        </div>
      </div>
      <ArrowUpRight className="h-3.5 w-3.5 shrink-0 text-zinc-500 transition group-hover:text-blue-300"/>
    </Link>
  );
}

function labelKind(k) {
  if (k === "topup") return "Top-up commission";
  if (k === "tier_bonus_1") return "Tier 1 bonus";
  if (k === "tier_bonus_2") return "Tier 2 bonus";
  return k;
}
