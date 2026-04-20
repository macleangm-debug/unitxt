import { forwardRef } from "react";

export function PageHeader({ overline, title, desc, actions, testid }) {  return (
    <div className="mb-6 flex flex-col gap-4 border-b border-zinc-900 pb-6 sm:flex-row sm:items-end sm:justify-between" data-testid={testid}>
      <div>
        {overline && <div className="label-overline">{overline}</div>}
        <h1 className="mt-2 font-display text-3xl font-semibold tracking-tight">{title}</h1>
        {desc && <p className="mt-1 text-sm text-zinc-500">{desc}</p>}
      </div>
      {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
    </div>
  );
}

export function Stat({ label, value, sub, accent = "white", testid }) {
  const acc = accent === "green" ? "text-emerald-400" : accent === "orange" ? "text-orange-400" : accent === "red" ? "text-red-400" : "text-white";
  return (
    <div className="card-surface p-5" data-testid={testid}>
      <div className="label-overline">{label}</div>
      <div className={`mt-3 font-mono text-3xl font-medium tracking-tight ${acc}`}>{value}</div>
      {sub && <div className="mt-1 text-xs text-zinc-500">{sub}</div>}
    </div>
  );
}

export function Pill({ status, children }) {
  const cls =
    status === "approved" || status === "delivered" || status === "active" || status === "completed" || status === "healthy" || status === "success" ? "pill-green"
    : status === "pending" || status === "scheduled" || status === "running" || status === "warning" ? "pill-orange"
    : status === "rejected" || status === "failed" || status === "down" || status === "error" ? "pill-red"
    : status === "info" ? "pill-blue"
    : "pill-zinc";
  return <span className={`pill ${cls}`} data-testid={`pill-${status}`}>{children || status}</span>;
}

export function Btn({ children, variant = "primary", className = "", ...rest }) {
  const base = "inline-flex items-center justify-center gap-2 rounded-sm h-10 px-4 text-sm font-medium transition disabled:opacity-50";
  const variants = {
    primary: "bg-white text-black hover:bg-zinc-200",
    secondary: "bg-zinc-900 text-white border border-zinc-800 hover:bg-zinc-800",
    ghost: "text-zinc-400 hover:text-white hover:bg-white/5",
    danger: "bg-red-500/10 border border-red-500/30 text-red-400 hover:bg-red-500/20",
  };
  return <button className={`${base} ${variants[variant]} ${className}`} {...rest}>{children}</button>;
}

export function Field({ label, children, hint }) {
  return (
    <label className="block">
      <span className="label-overline">{label}</span>
      <div className="mt-2">{children}</div>
      {hint && <div className="mt-1 text-xs text-zinc-500">{hint}</div>}
    </label>
  );
}

export function Input(props) {
  return <input {...props} className={`h-10 w-full border border-zinc-800 bg-transparent px-3 text-sm text-white outline-none transition focus:border-white ${props.className || ""}`} />;
}

export const TextArea = forwardRef(function TextArea(props, ref) {
  return <textarea ref={ref} {...props} className={`min-h-[120px] w-full border border-zinc-800 bg-transparent p-3 text-sm text-white outline-none transition focus:border-white ${props.className || ""}`} />;
});

export function Select(props) {
  return <select {...props} className={`h-10 w-full border border-zinc-800 bg-[#0A0A0A] px-3 text-sm text-white outline-none focus:border-white ${props.className || ""}`}>{props.children}</select>;
}

export function Empty({ title = "Nothing here yet.", desc, action }) {
  return (
    <div className="card-surface flex flex-col items-center justify-center gap-3 py-16 text-center">
      <div className="dot-pattern absolute inset-0 -z-0 opacity-30" />
      <div className="font-display text-lg font-semibold">{title}</div>
      {desc && <div className="max-w-md text-sm text-zinc-500">{desc}</div>}
      {action}
    </div>
  );
}

export function Card({ children, className = "", testid }) {
  return <div className={`card-surface p-5 ${className}`} data-testid={testid}>{children}</div>;
}

export function Modal({ open, onClose, title, children, testid }) {
  if (!open) return null;
  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" onClick={onClose} data-testid={testid}>
      <div className="w-full max-w-lg border border-zinc-800 bg-[#141414]" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between border-b border-zinc-900 px-5 py-3">
          <h3 className="font-display text-base font-semibold">{title}</h3>
          <button onClick={onClose} className="text-zinc-500 hover:text-white" data-testid="modal-close">✕</button>
        </div>
        <div className="p-5">{children}</div>
      </div>
    </div>
  );
}

export function Table({ columns, rows, empty = "No records.", rowKey = "id", testid }) {
  return (
    <div className="card-surface overflow-hidden" data-testid={testid}>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-zinc-900 bg-white/[0.02]">
              {columns.map((c) => (
                <th key={c.key} className="px-4 py-2.5 text-left label-overline">{c.label}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && (
              <tr><td colSpan={columns.length} className="px-4 py-12 text-center text-zinc-500">{empty}</td></tr>
            )}
            {rows.map((r) => (
              <tr key={r[rowKey]} className="border-b border-zinc-900 last:border-b-0 hover:bg-white/[0.02]">
                {columns.map((c) => (
                  <td key={c.key} className={`px-4 py-3 ${c.mono ? "font-mono" : ""} ${c.className || ""}`}>
                    {c.render ? c.render(r) : r[c.key]}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
