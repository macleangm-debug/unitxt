import { useState, useEffect } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { toast } from "sonner";
import { useAuth } from "@/context/AuthContext";
import { fmtErr } from "@/lib/api";
import { ArrowRight, Tag, CheckCircle2 } from "lucide-react";

const COUNTRIES = [
  ["TZ","Tanzania"],["KE","Kenya"],["UG","Uganda"],["ZM","Zambia"],
  ["GH","Ghana"],["NG","Nigeria"],["ZA","South Africa"],["RW","Rwanda"],
  ["US","United States"],["GB","United Kingdom"],["IN","India"],["AE","UAE"],
];

export default function Register() {
  const { register } = useAuth();
  const nav = useNavigate();
  const loc = useLocation();
  const [form, setForm] = useState({
    name: "", business_name: "", email: "", phone: "",
    password: "", country: "TZ", role: "client", referral_code: "",
  });
  const [busy, setBusy] = useState(false);
  const [refLocked, setRefLocked] = useState(false);

  useEffect(() => {
    const params = new URLSearchParams(loc.search);
    const r = params.get("ref");
    if (r) {
      setForm(f => ({ ...f, referral_code: r.toUpperCase() }));
      setRefLocked(true);
    }
  }, [loc.search]);

  const set = (k, v) => setForm((f) => ({ ...f, [k]: v }));

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    try {
      const u = await register({
        ...form,
        referral_code: (form.referral_code || "").toUpperCase().trim(),
      });
      toast.success(`Welcome ${u.name.split(" ")[0]}.`);
      if (u.role === "reseller") nav("/reseller/dashboard");
      else nav("/client/dashboard");
    } catch (err) {
      toast.error(fmtErr(err.response?.data?.detail) || err.message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="flex min-h-screen bg-[#0B0D10] text-zinc-100">
      <div className="hidden flex-1 border-r border-zinc-800/80 bg-[#0E1014] lg:flex">
        <div className="relative flex flex-1 flex-col justify-between p-12 grid-pattern">
          <div className="flex items-center gap-2.5">
            <div className="grid h-8 w-8 place-items-center rounded-md bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm shadow-blue-500/20">
              <span className="font-display text-sm font-bold">u</span>
            </div>
            <span className="font-display text-lg font-semibold tracking-tight">unitxt</span>
          </div>

          <div className="rise rise-2 max-w-md">
            <div className="label-overline text-blue-400">Create your workspace</div>
            <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight">
              Send your first SMS in 60 seconds.
            </h2>
            <p className="mt-3 text-sm leading-relaxed text-zinc-400">
              No credit card. 100 demo credits the moment you finish.
              Local SMS routes in 12 countries, WhatsApp included, full API access.
            </p>
            {refLocked && form.referral_code && (
              <div className="mt-6 rounded-lg border border-emerald-500/25 bg-emerald-500/[0.05] p-3 text-sm">
                <div className="flex items-center gap-2 text-emerald-400">
                  <CheckCircle2 className="h-4 w-4"/>
                  <span className="font-medium">Promo applied</span>
                </div>
                <div className="mt-1 text-xs text-zinc-300">
                  You're signing up via <span className="font-mono text-emerald-300">{form.referral_code}</span>.
                  You'll get a welcome bonus on your first paid top-up.
                </div>
              </div>
            )}
          </div>

          <div className="text-[11px] text-zinc-600">© 2026 unitxt — SMS & WhatsApp, global</div>
        </div>
      </div>

      <div className="flex flex-1 items-center justify-center p-6 sm:p-12">
        <form onSubmit={submit} className="w-full max-w-md">
          <h1 className="font-display text-2xl font-semibold tracking-tight">Get started</h1>
          <p className="mt-1 text-sm text-zinc-400">Tell us about you and your business.</p>

          <div className="mt-6 grid gap-4">
            {[
              ["name",          "Full name",     "text",     "Jane Mwangi"],
              ["business_name", "Company",       "text",     "Mwangi Telecom Ltd"],
              ["email",         "Work email",    "email",    "you@company.co.tz"],
              ["phone",         "Phone (optional)", "tel",   "+255712345678"],
              ["password",      "Password",      "password", "Min 8 characters"],
            ].map(([k, l, t, p]) => (
              <label key={k} className="block">
                <span className="text-xs font-medium text-zinc-300">{l}</span>
                <input
                  type={t}
                  required={k !== "business_name" && k !== "phone"}
                  value={form[k]}
                  onChange={(e) => set(k, e.target.value)}
                  data-testid={`reg-${k}`}
                  className="mt-1.5 h-10 w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 text-sm text-zinc-100 placeholder:text-zinc-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                  placeholder={p}
                />
              </label>
            ))}

            <div className="grid gap-3 sm:grid-cols-2">
              <label className="block">
                <span className="text-xs font-medium text-zinc-300">Country</span>
                <select
                  value={form.country}
                  onChange={(e) => set("country", e.target.value)}
                  data-testid="reg-country"
                  className="mt-1.5 h-10 w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 text-sm text-zinc-100 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                >
                  {COUNTRIES.map(([c, n]) => (<option key={c} value={c}>{n}</option>))}
                </select>
              </label>

              <label className="block">
                <span className="text-xs font-medium text-zinc-300">Account type</span>
                <select
                  value={form.role}
                  onChange={(e) => set("role", e.target.value)}
                  data-testid="reg-role"
                  className="mt-1.5 h-10 w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 text-sm text-zinc-100 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                >
                  <option value="client">Send messages</option>
                  <option value="reseller">Earn as affiliate</option>
                </select>
              </label>
            </div>

            <label className="block">
              <span className="flex items-center gap-1.5 text-xs font-medium text-zinc-300">
                <Tag className="h-3 w-3 text-blue-400" /> Promo code (optional)
              </span>
              <input
                type="text"
                value={form.referral_code}
                onChange={(e) => set("referral_code", e.target.value.toUpperCase())}
                disabled={refLocked}
                data-testid="reg-referral-code"
                className={`mt-1.5 h-10 w-full rounded-md border px-3 text-sm text-zinc-100 placeholder:text-zinc-500 outline-none focus:ring-2 focus:ring-blue-500/20 ${
                  refLocked
                    ? "border-emerald-500/30 bg-emerald-500/[0.05] font-mono"
                    : "border-zinc-800 bg-zinc-950/60 focus:border-blue-500"
                }`}
                placeholder="JOHN20"
              />
              <span className="mt-1 block text-[11px] leading-relaxed text-zinc-500">
                Have a promo code from an affiliate or a unitxt campaign? Drop it here for a welcome bonus on your first paid top-up.
              </span>
            </label>
          </div>

          <button
            type="submit"
            disabled={busy}
            data-testid="register-submit"
            className="mt-7 inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-blue-600 text-sm font-medium text-white transition hover:bg-blue-500 disabled:opacity-50"
          >
            {busy ? "Creating…" : "Create workspace"} <ArrowRight className="h-4 w-4"/>
          </button>

          <p className="mt-4 text-center text-sm text-zinc-500">
            Have an account?{" "}
            <Link to="/login" className="text-blue-400 underline-offset-4 hover:underline" data-testid="register-to-login">
              Sign in
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
