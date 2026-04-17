import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { toast } from "sonner";
import { useAuth } from "@/context/AuthContext";
import { fmtErr } from "@/lib/api";
import { ArrowRight } from "lucide-react";

export default function Login() {
  const { login } = useAuth();
  const nav = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [busy, setBusy] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    try {
      const u = await login(email, password);
      toast.success(`Welcome back, ${u.name.split(" ")[0]}`);
      if (u.role === "super_admin" || u.role === "country_admin") nav("/admin/overview");
      else if (u.role === "reseller") nav("/reseller/dashboard");
      else nav("/client/dashboard");
    } catch (err) {
      toast.error(fmtErr(err.response?.data?.detail) || err.message);
    } finally {
      setBusy(false);
    }
  };

  const fillDemo = (kind) => {
    if (kind === "admin") { setEmail("admin@unitxt.io"); setPassword("Admin@2026"); }
    if (kind === "reseller") { setEmail("reseller@unitxt.io"); setPassword("Reseller@2026"); }
    if (kind === "client") { setEmail("client@unitxt.io"); setPassword("Client@2026"); }
  };

  return (
    <div className="flex min-h-screen bg-[#0A0A0A] text-white">
      <div className="hidden flex-1 border-r border-zinc-900 lg:flex">
        <div className="relative flex flex-1 flex-col justify-between p-12 grid-pattern">
          <Link to="/" className="flex items-center gap-2" data-testid="login-brand">
            <div className="grid h-7 w-7 place-items-center bg-white text-black"><span className="font-display text-sm font-bold">u</span></div>
            <span className="font-display text-lg font-bold">unitxt</span>
          </Link>
          <div>
            <div className="label-overline">Control room access</div>
            <p className="mt-4 max-w-md font-display text-3xl font-semibold tracking-tight">
              Twelve countries. Three providers. One console.
            </p>
            <p className="mt-3 max-w-md text-sm text-zinc-400">
              Sign in to dispatch messages, manage resellers, or run the platform.
            </p>
          </div>
          <div className="font-mono text-[10px] tracking-widest text-zinc-600">SECURE LINK · TLS 1.3 · v1.0</div>
        </div>
      </div>
      <div className="flex flex-1 items-center justify-center p-6">
        <form onSubmit={submit} className="w-full max-w-sm" data-testid="login-form">
          <div className="label-overline">Sign in</div>
          <h1 className="mt-2 font-display text-3xl font-semibold tracking-tight">Welcome back.</h1>
          <p className="mt-2 text-sm text-zinc-500">Use your unitxt credentials.</p>

          <label className="mt-8 block">
            <span className="label-overline">Email</span>
            <input
              type="email" required value={email} onChange={(e) => setEmail(e.target.value)}
              data-testid="login-email"
              className="mt-2 h-11 w-full border border-zinc-800 bg-transparent px-3 text-sm text-white outline-none transition focus:border-white"
              placeholder="you@company.com"
            />
          </label>
          <label className="mt-4 block">
            <span className="label-overline">Password</span>
            <input
              type="password" required value={password} onChange={(e) => setPassword(e.target.value)}
              data-testid="login-password"
              className="mt-2 h-11 w-full border border-zinc-800 bg-transparent px-3 text-sm text-white outline-none transition focus:border-white"
              placeholder="••••••••"
            />
          </label>

          <button
            type="submit" disabled={busy} data-testid="login-submit"
            className="mt-6 inline-flex h-11 w-full items-center justify-center gap-2 bg-white text-sm font-medium text-black transition hover:bg-zinc-200 disabled:opacity-50"
          >
            {busy ? "Signing in…" : "Sign in"} <ArrowRight className="h-4 w-4" />
          </button>

          <div className="mt-6 border border-zinc-900 p-3">
            <div className="label-overline">Demo accounts · click to fill</div>
            <div className="mt-3 grid grid-cols-3 gap-2">
              {["admin","reseller","client"].map(k=>(
                <button type="button" key={k} onClick={()=>fillDemo(k)}
                  data-testid={`demo-fill-${k}`}
                  className="border border-zinc-800 bg-zinc-950 py-1.5 text-[11px] font-mono uppercase tracking-widest text-zinc-400 hover:border-zinc-600 hover:text-white">
                  {k}
                </button>
              ))}
            </div>
          </div>

          <p className="mt-6 text-center text-sm text-zinc-500">
            New here?{" "}
            <Link to="/register" className="text-white underline-offset-4 hover:underline" data-testid="login-to-register">
              Create a workspace
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
