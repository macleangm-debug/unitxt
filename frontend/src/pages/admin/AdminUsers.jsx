import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, shortDate } from "@/lib/api";
import {
  PageHeader, Card, Field, Input, Select, Btn, Table, Pill, Stat,
} from "@/components/UI";
import {
  UserCog, Search, Activity, PauseCircle, PlayCircle, DollarSign, Edit,
} from "lucide-react";

const ROLES = ["client", "reseller", "staff", "support", "finance", "compliance", "country_admin", "super_admin"];

function UserDrawer({ user, onClose, onSaved }) {
  const [edit, setEdit] = useState(null);
  const [creditAmt, setCreditAmt] = useState("");
  const [creditNote, setCreditNote] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (user) setEdit({ name: user.name, role: user.role, status: user.status });
    setCreditAmt(""); setCreditNote("");
  }, [user]);

  if (!user || !edit) return null;

  const run = async (fn) => {
    setBusy(true);
    try { await fn(); toast.success("Saved"); onSaved?.(); }
    catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const savePatch = () => run(() => http.patch(`/admin/users/${user.id}`, edit));
  const toggleStatus = () => {
    const next = user.status === "active" ? "suspended" : "active";
    run(() => http.patch(`/admin/users/${user.id}`, { status: next }));
  };
  const credit = () => run(async () => {
    const n = Number(creditAmt);
    if (!n) throw { response: { data: { detail: "Enter non-zero amount" } } };
    await http.post(`/admin/users/${user.id}/credit`, {
      amount: n, method: "manual", note: creditNote,
    });
    setCreditAmt(""); setCreditNote("");
  });

  return (
    <div className="fixed inset-0 z-[100] flex justify-end bg-black/70" onClick={onClose} data-testid="user-drawer">
      <div className="h-full w-full max-w-2xl overflow-y-auto border-l border-zinc-800 bg-[#0e0e0e]" onClick={(e) => e.stopPropagation()}>
        <div className="sticky top-0 flex items-center justify-between border-b border-zinc-900 bg-[#0e0e0e]/95 px-6 py-4 backdrop-blur">
          <div>
            <div className="label-overline">User</div>
            <h2 className="mt-1 font-display text-xl font-semibold tracking-tight">{user.name || user.email}</h2>
            <div className="mt-0.5 text-xs text-zinc-500">{user.email}</div>
          </div>
          <button onClick={onClose} className="text-zinc-400 hover:text-white" data-testid="user-drawer-close">✕</button>
        </div>

        <div className="grid gap-4 p-6">
          <div className="grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
            <Stat label="Role" value={user.role}/>
            <Stat label="Country" value={user.country || "—"}/>
            <Stat label="Status" value={user.status}/>
            <Stat label="Joined" value={shortDate(user.created_at)}/>
          </div>

          {/* Suspend / reactivate */}
          <div className="flex items-center justify-between border border-zinc-900 bg-[#141414] p-4">
            <div>
              <div className="font-medium">Account status</div>
              <div className="text-xs text-zinc-500">Suspend blocks login, sends and wallet ops.</div>
            </div>
            <Btn variant="ghost" onClick={toggleStatus} disabled={busy} data-testid="user-toggle-status">
              {user.status === "active"
                ? (<><PauseCircle className="h-4 w-4"/>Suspend</>)
                : (<><PlayCircle className="h-4 w-4"/>Reactivate</>)}
            </Btn>
          </div>

          {/* Profile edit */}
          <Card testid="user-edit-card">
            <div className="label-overline mb-3 flex items-center gap-2"><Edit className="h-3.5 w-3.5"/>Profile</div>
            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="Name">
                <Input value={edit.name || ""} onChange={(e) => setEdit({ ...edit, name: e.target.value })}
                       data-testid="user-edit-name"/>
              </Field>
              <Field label="Role">
                <Select value={edit.role} onChange={(e) => setEdit({ ...edit, role: e.target.value })}
                         data-testid="user-edit-role">
                  {ROLES.map(r => <option key={r} value={r}>{r.replace(/_/g, " ")}</option>)}
                </Select>
              </Field>
            </div>
            <div className="mt-3">
              <Btn onClick={savePatch} disabled={busy} data-testid="user-save">Save profile</Btn>
            </div>
          </Card>

          {/* Credit adjustment */}
          <Card testid="user-credit-card">
            <div className="label-overline mb-3 flex items-center gap-2">
              <DollarSign className="h-3.5 w-3.5"/>Credit wallet
            </div>
            <div className="grid gap-3 sm:grid-cols-[1fr,2fr,auto]">
              <Field label="Credits to adjust" hint="Positive to credit, negative to debit.">
                <Input type="number" value={creditAmt}
                       onChange={(e) => setCreditAmt(e.target.value)}
                       data-testid="user-credit-amt"/>
              </Field>
              <Field label="Note">
                <Input value={creditNote} onChange={(e) => setCreditNote(e.target.value)}
                       placeholder="Manual top-up, refund, bonus..."/>
              </Field>
              <Btn onClick={credit} disabled={busy} className="h-10 self-end" data-testid="user-credit-go">
                Apply
              </Btn>
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
}

export default function AdminUsers() {
  const [rows, setRows] = useState([]);
  const [q, setQ] = useState("");
  const [roleFilter, setRoleFilter] = useState("all");
  const [open, setOpen] = useState(null);

  const load = () => http.get("/admin/users").then(r => setRows(r.data)).catch(() => {});
  useEffect(() => { load(); }, []);

  const filtered = useMemo(() => {
    let list = rows;
    if (roleFilter !== "all") list = list.filter(r => r.role === roleFilter);
    const needle = q.trim().toLowerCase();
    if (needle) list = list.filter(r => [r.email, r.name, r.country, r.role]
      .filter(Boolean).some(v => String(v).toLowerCase().includes(needle)));
    return list;
  }, [rows, q, roleFilter]);

  const totals = useMemo(() => ({
    total: rows.length,
    clients: rows.filter(r => r.role === "client").length,
    resellers: rows.filter(r => r.role === "reseller").length,
    suspended: rows.filter(r => r.status === "suspended").length,
  }), [rows]);

  return (
    <div>
      <PageHeader
        overline="Platform ops"
        title="Users"
        desc="Suspend, change role, or credit-adjust any account. Click a row to open the management drawer."
        actions={
          <Btn variant="ghost" onClick={load}><Activity className="h-4 w-4"/>Refresh</Btn>
        }
      />

      <div className="mb-4 grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
        <Stat label="Total users"  value={totals.total} accent="white"/>
        <Stat label="Clients"      value={totals.clients}/>
        <Stat label="Resellers"    value={totals.resellers}/>
        <Stat label="Suspended"    value={totals.suspended} accent={totals.suspended > 0 ? "orange" : "white"}/>
      </div>

      <div className="mb-4 flex items-center gap-3 border border-zinc-900 bg-[#141414] px-3 py-2">
        <div className="relative max-w-sm flex-1">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" strokeWidth={1.5}/>
          <Input placeholder="Search email, name, country..." value={q}
                 onChange={(e) => setQ(e.target.value)} className="pl-9"
                 data-testid="users-search"/>
        </div>
        <Select value={roleFilter} onChange={(e) => setRoleFilter(e.target.value)} className="w-40" data-testid="users-role-filter">
          <option value="all">All roles</option>
          {ROLES.map(r => <option key={r} value={r}>{r.replace(/_/g, " ")}</option>)}
        </Select>
      </div>

      <Table
        testid="users-table"
        rows={filtered}
        empty="No users match."
        columns={[
          {
            key: "name", label: "User",
            render: r => (
              <button onClick={() => setOpen(r)} className="text-left hover:text-white" data-testid={`open-user-${r.id}`}>
                <div className="text-sm font-medium text-white">{r.name || r.email}</div>
                <div className="font-mono text-[11px] text-zinc-500">{r.email}</div>
              </button>
            ),
          },
          { key: "role", label: "Role", render: r => (
            <Pill status={r.role === "super_admin" ? "info" : r.role === "reseller" ? "approved" : "pending"}>
              {r.role.replace(/_/g, " ")}
            </Pill>
          ) },
          { key: "country", label: "Country", mono: true },
          { key: "status", label: "Status", render: r => <Pill status={r.status}/> },
          { key: "created_at", label: "Joined", mono: true, render: r => shortDate(r.created_at) },
        ]}
      />

      <UserDrawer user={open} onClose={() => setOpen(null)} onSaved={() => { load(); }}/>
    </div>
  );
}
