import { useState } from "react";
import { Link, useParams, useNavigate } from "react-router-dom";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { Card, Field, Input, Select, TextArea, Btn, Stat } from "@/components/UI";
import {
  Users, Building2, CheckCircle2, ArrowLeft, Percent, Wallet as WalletIcon,
  Gauge, ShieldCheck, Zap,
} from "lucide-react";

const RESELLER_EMPTY = {
  company_name: "", contact_name: "", email: "", phone: "",
  country: "TZ", website: "",
  expected_monthly_volume: 100000, pitch: "", agree_terms: false,
};

const INSTITUTION_EMPTY = {
  institution_name: "", institution_type: "bank",
  contact_name: "", email: "", phone: "", country: "TZ",
  use_cases: "", expected_monthly_volume: 100000,
  api_integration_needed: true,
};

export default function ApplyPage() {
  const { kind } = useParams();  // "reseller" | "institution"
  const nav = useNavigate();
  const isReseller = kind === "reseller";
  const [form, setForm] = useState(isReseller ? { ...RESELLER_EMPTY } : { ...INSTITUTION_EMPTY });
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(null);

  const set = (patch) => setForm({ ...form, ...patch });

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    try {
      const body = isReseller
        ? {
            ...form,
            expected_monthly_volume: Number(form.expected_monthly_volume) || 0,
          }
        : {
            ...form,
            expected_monthly_volume: Number(form.expected_monthly_volume) || 0,
          };
      const r = await http.post(`/public/apply/${kind}`, body);
      setDone({ id: r.data.id });
      toast.success("Application received");
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  if (done) {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white">
        <TopBar/>
        <div className="mx-auto max-w-xl px-6 py-24 text-center">
          <CheckCircle2 className="mx-auto mb-6 h-12 w-12 text-emerald-400" strokeWidth={1.5}/>
          <h1 className="font-display text-3xl font-semibold tracking-tight">Application received</h1>
          <p className="mt-3 text-sm text-zinc-400">
            We'll review your submission and get back to you within 3-5 business days.
            Your reference: <code className="font-mono text-white">{done.id.slice(0, 8)}</code>
          </p>
          <Btn className="mt-6" onClick={() => nav("/")}>Back to home</Btn>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#0A0A0A] text-white">
      <TopBar/>
      <div className="mx-auto max-w-6xl px-6 py-10 lg:grid lg:grid-cols-[1.2fr,1fr] lg:gap-10">
        {/* Left: pitch */}
        <div>
          <Link to="/" className="mb-6 inline-flex items-center gap-1 text-xs text-zinc-500 hover:text-white">
            <ArrowLeft className="h-3 w-3"/>unitxt
          </Link>
          <div className="label-overline">{isReseller ? "Reseller program" : "Institutional integration"}</div>
          <h1 className="mt-2 font-display text-4xl font-semibold tracking-tight sm:text-5xl">
            {isReseller ? (
              <>Resell unitxt.<br/>Keep your margins.</>
            ) : (
              <>Wire your bank,<br/>fintech or telco.</>
            )}
          </h1>
          <p className="mt-4 max-w-md text-sm text-zinc-400">
            {isReseller
              ? "Bring us your clients. We handle the infra, you earn commission on every send — straight out of our margin. No inflated retail, no double-billing."
              : "Integrate unitxt's messaging backbone into your OTP, fraud alerts, collections and marketing flows. REST API, DLR webhooks, dedicated sender IDs, SLA."}
          </p>

          {isReseller ? (
            <div className="mt-8 grid grid-cols-2 gap-0 border border-zinc-900">
              <Stat label="Default commission" value="15%" accent="green"/>
              <Stat label="Client overcharge"  value="0%"/>
              <Stat label="Payout"             value="Instant" accent="green"/>
              <Stat label="Float requirement"  value="Flexible"/>
            </div>
          ) : (
            <div className="mt-8 grid grid-cols-2 gap-0 border border-zinc-900">
              <Stat label="Queue capacity"      value="200k+" accent="green"/>
              <Stat label="Delivery p95"        value="<30s"/>
              <Stat label="DLR webhook"         value="Real-time"/>
              <Stat label="Uptime target"       value="99.9%"/>
            </div>
          )}

          <div className="mt-8 space-y-4 text-sm text-zinc-400">
            {(isReseller ? RESELLER_BENEFITS : INSTITUTION_BENEFITS).map((b) => (
              <div key={b.title} className="flex gap-3">
                <b.icon className="mt-0.5 h-4 w-4 shrink-0 text-emerald-400" strokeWidth={1.5}/>
                <div>
                  <div className="text-white">{b.title}</div>
                  <div>{b.body}</div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Right: form */}
        <div className="mt-10 lg:mt-0">
          <Card>
            <div className="label-overline mb-1 flex items-center gap-2">
              {isReseller ? <Users className="h-3.5 w-3.5"/> : <Building2 className="h-3.5 w-3.5"/>}
              Apply now
            </div>
            <p className="mb-5 text-xs text-zinc-500">We review every application within 3-5 business days.</p>
            <form onSubmit={submit} className="space-y-3">
              {isReseller ? (
                <>
                  <Field label="Company name">
                    <Input value={form.company_name} onChange={(e) => set({ company_name: e.target.value })} required data-testid="apply-company"/>
                  </Field>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Your name"><Input value={form.contact_name} onChange={(e) => set({ contact_name: e.target.value })} required data-testid="apply-name"/></Field>
                    <Field label="Email"><Input type="email" value={form.email} onChange={(e) => set({ email: e.target.value })} required data-testid="apply-email"/></Field>
                  </div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Phone (optional)"><Input value={form.phone} onChange={(e) => set({ phone: e.target.value })}/></Field>
                    <Field label="Country (ISO-2)"><Input value={form.country} maxLength={2} onChange={(e) => set({ country: e.target.value.toUpperCase() })} className="uppercase"/></Field>
                  </div>
                  <Field label="Website (optional)"><Input value={form.website} onChange={(e) => set({ website: e.target.value })} placeholder="https://..."/></Field>
                  <Field label="Expected monthly volume (SMS)" hint="Ballpark — we won't hold you to it.">
                    <Input type="number" value={form.expected_monthly_volume} onChange={(e) => set({ expected_monthly_volume: e.target.value })}/>
                  </Field>
                  <Field label="Why unitxt?" hint="A line or two about your business and plan.">
                    <TextArea value={form.pitch} onChange={(e) => set({ pitch: e.target.value })} className="min-h-[80px]"/>
                  </Field>
                  <label className="flex items-start gap-2 text-xs text-zinc-400">
                    <input type="checkbox" checked={form.agree_terms} onChange={(e) => set({ agree_terms: e.target.checked })} data-testid="apply-terms"/>
                    I agree to the reseller terms — I will not overcharge my clients, and commissions are paid from platform margin only.
                  </label>
                </>
              ) : (
                <>
                  <Field label="Institution name">
                    <Input value={form.institution_name} onChange={(e) => set({ institution_name: e.target.value })} required data-testid="apply-inst-name"/>
                  </Field>
                  <Field label="Institution type">
                    <Select value={form.institution_type} onChange={(e) => set({ institution_type: e.target.value })}>
                      <option value="bank">Bank</option>
                      <option value="mobile_money">Mobile money</option>
                      <option value="fintech">Fintech</option>
                      <option value="enterprise">Enterprise</option>
                    </Select>
                  </Field>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Your name"><Input value={form.contact_name} onChange={(e) => set({ contact_name: e.target.value })} required data-testid="apply-inst-contact"/></Field>
                    <Field label="Email"><Input type="email" value={form.email} onChange={(e) => set({ email: e.target.value })} required data-testid="apply-inst-email"/></Field>
                  </div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Phone (optional)"><Input value={form.phone} onChange={(e) => set({ phone: e.target.value })}/></Field>
                    <Field label="Country (ISO-2)"><Input value={form.country} maxLength={2} onChange={(e) => set({ country: e.target.value.toUpperCase() })} className="uppercase"/></Field>
                  </div>
                  <Field label="Use cases" hint="OTP, fraud alerts, collections, marketing...">
                    <TextArea value={form.use_cases} onChange={(e) => set({ use_cases: e.target.value })} className="min-h-[80px]"/>
                  </Field>
                  <Field label="Expected monthly volume (messages)">
                    <Input type="number" value={form.expected_monthly_volume} onChange={(e) => set({ expected_monthly_volume: e.target.value })}/>
                  </Field>
                </>
              )}

              <Btn type="submit" disabled={busy || (isReseller && !form.agree_terms)} className="w-full mt-4" data-testid="apply-submit">
                {busy ? "Submitting..." : "Submit application"}
              </Btn>
            </form>
          </Card>
        </div>
      </div>
    </div>
  );
}

function TopBar() {
  return (
    <div className="border-b border-zinc-900 bg-[#0A0A0A]/95 backdrop-blur">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <Link to="/" className="flex items-center gap-2" data-testid="apply-brand">
          <div className="grid h-6 w-6 place-items-center bg-white text-black">
            <span className="font-display text-[10px] font-bold">u</span>
          </div>
          <span className="font-display text-sm font-bold">unitxt</span>
        </Link>
        <div className="flex items-center gap-5 text-xs">
          <Link to="/apply/reseller" className="text-zinc-400 hover:text-white">Become a reseller</Link>
          <Link to="/apply/institution" className="text-zinc-400 hover:text-white">Integrate with us</Link>
          <Link to="/login" className="text-white underline underline-offset-4">Log in</Link>
        </div>
      </div>
    </div>
  );
}

const RESELLER_BENEFITS = [
  { icon: Percent,      title: "Loss-proof commission",
    body: "Clients always pay our retail rate. Your 15%+ is paid from our margin, instantly on every send." },
  { icon: WalletIcon,   title: "Keep your own float",
    body: "Top up once, serve unlimited clients. We never touch your wallet for system fees." },
  { icon: Gauge,        title: "Enterprise-grade queue",
    body: "100k+ recipients in a single campaign, operator-aware routing, DLR webhook push." },
  { icon: ShieldCheck,  title: "KYC & compliance, handled",
    body: "Sender ID approvals, spam-filtering and opt-out footers are built in per country." },
];

const INSTITUTION_BENEFITS = [
  { icon: Zap,          title: "REST API + DLR webhook",
    body: "Fire SMS / WhatsApp from your backend; we push every status change back as JSON." },
  { icon: ShieldCheck,  title: "Dedicated sender IDs",
    body: "Regulatory-compliant branded IDs per country. We manage approval." },
  { icon: Gauge,        title: "Scale without queue engineering",
    body: "Built-in 200k+ throughput, per-provider concurrency control, auto-retry." },
  { icon: WalletIcon,   title: "Credit-based billing",
    body: "Pay once in credits, use anywhere; country-specific rates; transparent margin." },
];
