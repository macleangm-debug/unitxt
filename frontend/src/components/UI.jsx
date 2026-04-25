import { forwardRef } from "react";

export function PageHeader({ overline, title, desc, actions, testid }) {
  return (
    <div
      className="mb-6 flex flex-col gap-4 border-b border-zinc-800/80 pb-6 sm:flex-row sm:items-end sm:justify-between"
      data-testid={testid}
    >
      <div>
        {overline && <div className="label-overline">{overline}</div>}
        <h1 className="mt-2 font-display text-2xl font-semibold tracking-tight text-zinc-100 sm:text-[26px]">
          {title}
        </h1>
        {desc && <p className="mt-1.5 max-w-2xl text-sm leading-relaxed text-zinc-400">{desc}</p>}
      </div>
      {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
    </div>
  );
}

export function Stat({ label, value, sub, accent = "white", testid }) {
  const acc =
    accent === "green"  ? "text-emerald-400" :
    accent === "orange" ? "text-amber-400"   :
    accent === "red"    ? "text-red-400"     :
    accent === "blue"   ? "text-blue-400"    : "text-zinc-100";
  return (
    <div className="card-surface p-5" data-testid={testid}>
      <div className="label-overline">{label}</div>
      <div className={`mt-2.5 font-mono text-3xl font-medium tracking-tight ${acc}`}>{value}</div>
      {sub && <div className="mt-1 text-xs text-zinc-500">{sub}</div>}
    </div>
  );
}

export function Pill({ status, children }) {
  const cls =
    status === "approved" || status === "delivered" || status === "active" ||
    status === "completed" || status === "healthy" || status === "success" ? "pill-green"
    : status === "pending" || status === "scheduled" || status === "running" || status === "warning" ? "pill-orange"
    : status === "rejected" || status === "failed" || status === "down" || status === "error" ? "pill-red"
    : status === "info" ? "pill-blue"
    : "pill-zinc";
  return <span className={`pill ${cls}`} data-testid={`pill-${status}`}>{children || status}</span>;
}

export function Btn({ children, variant = "primary", className = "", ...rest }) {
  const base =
    "inline-flex items-center justify-center gap-2 rounded-md h-10 px-4 text-sm font-medium " +
    "transition-[background-color,border-color,opacity,transform] duration-150 " +
    "disabled:opacity-50 disabled:cursor-not-allowed " +
    "focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0B0D10]";
  const variants = {
    primary:   "bg-blue-600 text-white hover:bg-blue-500 shadow-sm shadow-blue-500/10",
    secondary: "bg-zinc-800/80 text-zinc-100 border border-zinc-700/80 hover:bg-zinc-700/80",
    ghost:     "text-zinc-300 hover:text-white hover:bg-white/5",
    danger:    "bg-red-500/10 border border-red-500/30 text-red-400 hover:bg-red-500/20",
  };
  return <button className={`${base} ${variants[variant]} ${className}`} {...rest}>{children}</button>;
}

export function Field({ label, children, hint }) {
  return (
    <label className="block">
      <span className="text-xs font-medium text-zinc-300">{label}</span>
      <div className="mt-1.5">{children}</div>
      {hint && <div className="mt-1 text-[11px] leading-relaxed text-zinc-500">{hint}</div>}
    </label>
  );
}

const inputBase =
  "w-full border border-zinc-800 bg-zinc-950/60 text-sm text-zinc-100 placeholder:text-zinc-500 " +
  "transition-[border-color,box-shadow] duration-150 outline-none rounded-md " +
  "focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20";

export function Input(props) {
  return (
    <input
      {...props}
      className={`h-10 px-3 ${inputBase} ${props.className || ""}`}
    />
  );
}

export const TextArea = forwardRef(function TextArea(props, ref) {
  return (
    <textarea
      ref={ref}
      {...props}
      className={`min-h-[120px] p-3 ${inputBase} ${props.className || ""}`}
    />
  );
});

export function Select(props) {
  return (
    <select
      {...props}
      className={`h-10 px-3 ${inputBase} ${props.className || ""}`}
    >
      {props.children}
    </select>
  );
}

export function Empty({ title = "Nothing here yet.", desc, action }) {
  return (
    <div className="card-surface relative flex flex-col items-center justify-center gap-3 py-16 text-center">
      <div className="dot-pattern absolute inset-0 -z-0 opacity-30" />
      <div className="font-display text-lg font-semibold text-zinc-100">{title}</div>
      {desc && <div className="max-w-md text-sm text-zinc-400">{desc}</div>}
      {action}
    </div>
  );
}

export function Card({ children, className = "", testid }) {
  return (
    <div className={`card-surface p-5 ${className}`} data-testid={testid}>
      {children}
    </div>
  );
}

export function Modal({ open, onClose, title, children, testid }) {
  if (!open) return null;
  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      onClick={onClose}
      data-testid={testid}
    >
      <div
        className="w-full max-w-lg overflow-hidden rounded-xl border border-zinc-800 bg-[#14171C] shadow-2xl shadow-black/40"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between border-b border-zinc-800/80 px-5 py-3.5">
          <h3 className="font-display text-base font-semibold text-zinc-100">{title}</h3>
          <button
            onClick={onClose}
            className="grid h-7 w-7 place-items-center rounded-md text-zinc-400 transition hover:bg-white/5 hover:text-white"
            data-testid="modal-close"
          >✕</button>
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
            <tr className="border-b border-zinc-800/80 bg-white/[0.015]">
              {columns.map((c) => (
                <th
                  key={c.key}
                  className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-500"
                >
                  {c.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && (
              <tr>
                <td colSpan={columns.length} className="px-4 py-12 text-center text-sm text-zinc-500">
                  {empty}
                </td>
              </tr>
            )}
            {rows.map((r) => (
              <tr
                key={r[rowKey]}
                className="border-b border-zinc-800/60 last:border-b-0 transition-colors hover:bg-white/[0.02]"
              >
                {columns.map((c) => (
                  <td
                    key={c.key}
                    className={`px-4 py-3 text-zinc-300 ${c.mono ? "font-mono" : ""} ${c.className || ""}`}
                  >
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
