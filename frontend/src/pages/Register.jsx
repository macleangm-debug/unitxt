import { useState, useEffect } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { toast } from "sonner";
import { useAuth } from "@/context/AuthContext";
import { fmtErr } from "@/lib/api";
import { ArrowRight } from "lucide-react";

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
    password: "", country: "TZ", role: "client", reseller_code: "", referral_code: "",
  });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    const params = new URLSearchParams(loc.search);
    const r = params.get("ref");
    if (r) setForm(f => ({ ...f, referral_code: r }));
  }, [loc.search]);

  const set = (k, v) => setForm((f) => ({ ...f, [k]: v }));

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    try {
      const u = await register(form);
      toast.success(`Workspace created. Welcome ${u.name.split(" ")[0]}.`);
      if (u.role === "reseller") nav("/reseller/dashboard");
      else nav("/client/dashboard");
    } catch (err) {
      toast.error(fmtErr(err.response?.data?.detail) || err.message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="flex min-h-screen bg-[#0A0A0A] text-white">
      <div className="hidden flex-1 border-r border-zinc-900 lg:flex">
        <div className="relative flex flex-1 flex-col justify-between p-12 grid-pattern">
          <Link to="/" className="flex items-center gap-2"><div className="grid h-7 w-7 place-items-center bg-white text-black"><span className="font-display text-sm font-bold">u</span></div><span className="font-display text-lg font-bold">unitxt</span></Link>
          <div>
            <div className="label-overline">Onboarding</div>
            <p className="mt-4 max-w-md font-display text-3xl font-semibold tracking-tight">
              Sixty seconds to your first dispatch.
            </p>
            <p className="mt-3 max-w-md text-sm text-zinc-400">
              Workspaces ship with a demo wallet, a default sender ID, and the mock provider on. Add Twilio
              keys when you're ready to go live.
            </p>
          </div>
          <div className="font-mono text-[10px] tracking-widest text-zinc-600">FREE · NO CARD · INSTANT</div>
        </div>
      </div>
      <div className="flex flex-1 items-center justify-center p-6">
        <form onSubmit={submit} className="w-full max-w-md" data-testid="register-form">
          <div className="label-overline">Create workspace</div>
          <h1 className="mt-2 font-display text-3xl font-semibold tracking-tight">Get started.</h1>

          <div className="mt-6 grid grid-cols-2 gap-2">
            {[["client","Client"],["reseller","Reseller"]].map(([v,l])=>(
              <button key={v} type="button" onClick={()=>set("role", v)}
                data-testid={`role-${v}`}
                className={`border px-3 py-2 text-sm font-medium transition ${form.role===v?"border-white bg-white text-black":"border-zinc-800 text-zinc-400 hover:border-zinc-600"}`}>
                {l}
              </button>
            ))}
          </div>

          {[
            ["name","Full name","text","Jane Doe"],
            ["business_name","Business name","text","Acme Ltd"],
            ["email","Work email","email","you@company.com"],
            ["phone","Phone","tel","+255712345678"],
            ["password","Password","password","Min 6 characters"],
          ].map(([k,l,t,p])=>(
            <label key={k} className="mt-4 block">
              <span className="label-overline">{l}</span>
              <input type={t} required={k!=="business_name"&&k!=="phone"} value={form[k]} onChange={(e)=>set(k,e.target.value)}
                data-testid={`reg-${k}`}
                className="mt-2 h-11 w-full border border-zinc-800 bg-transparent px-3 text-sm text-white outline-none focus:border-white"
                placeholder={p}/>
            </label>
          ))}

          <label className="mt-4 block">
            <span className="label-overline">Country</span>
            <select value={form.country} onChange={(e)=>set("country", e.target.value)}
              data-testid="reg-country"
              className="mt-2 h-11 w-full border border-zinc-800 bg-[#0A0A0A] px-3 text-sm text-white outline-none focus:border-white">
              {COUNTRIES.map(([c,n])=>(<option key={c} value={c}>{n}</option>))}
            </select>
          </label>

          {form.role==="client" && (
            <>
              <label className="mt-4 block">
                <span className="label-overline">Reseller code (optional)</span>
                <input value={form.reseller_code} onChange={(e)=>set("reseller_code", e.target.value)}
                  data-testid="reg-reseller-code"
                  className="mt-2 h-11 w-full border border-zinc-800 bg-transparent px-3 text-sm text-white outline-none focus:border-white"
                  placeholder="RDEMO1"/>
              </label>
              <label className="mt-4 block">
                <span className="label-overline">Referral code (optional)</span>
                <input value={form.referral_code} onChange={(e)=>set("referral_code", e.target.value)}
                  data-testid="reg-referral-code"
                  className="mt-2 h-11 w-full border border-zinc-800 bg-transparent px-3 text-sm text-white outline-none focus:border-white"
                  placeholder="From a friend"/>
              </label>
            </>
          )}

          <button type="submit" disabled={busy} data-testid="register-submit"
            className="mt-6 inline-flex h-11 w-full items-center justify-center gap-2 bg-white text-sm font-medium text-black transition hover:bg-zinc-200 disabled:opacity-50">
            {busy ? "Creating…" : "Create workspace"} <ArrowRight className="h-4 w-4"/>
          </button>

          <p className="mt-4 text-center text-sm text-zinc-500">
            Have an account? <Link to="/login" className="text-white underline-offset-4 hover:underline" data-testid="register-to-login">Sign in</Link>
          </p>
        </form>
      </div>
    </div>
  );
}
