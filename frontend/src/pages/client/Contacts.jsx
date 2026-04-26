import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import {
  PageHeader, Card, Field, Input, TextArea, Btn, Table, Modal, Pill, Stat,
} from "@/components/UI";
import {
  Plus, Trash2, Users, Folder, Edit, Check, X, UserPlus, FolderPlus,
  Search, BarChart3,
} from "lucide-react";

export default function Contacts() {
  const [contacts, setContacts] = useState([]);
  const [groups, setGroups] = useState([]);
  const [activeGroup, setActiveGroup] = useState("all");
  const [q, setQ] = useState("");
  const [selected, setSelected] = useState(new Set());
  const [statsFor, setStatsFor] = useState(null);   // group object whose stats are open

  const [addOpen, setAddOpen] = useState(false);
  const [groupOpen, setGroupOpen] = useState(false);
  const [editGroup, setEditGroup] = useState(null);
  const [form, setForm] = useState({ phone: "", name: "", extras_text: "", group_ids: [] });
  const [groupForm, setGroupForm] = useState({ name: "", description: "" });

  const load = async () => {
    const [c, g] = await Promise.all([http.get("/contacts"), http.get("/contacts/groups")]);
    setContacts(c.data);
    setGroups(g.data);
  };
  useEffect(() => { load().catch(() => {}); }, []);

  const filtered = useMemo(() => {
    let list = contacts;
    if (activeGroup === "ungrouped") {
      list = list.filter(c => !(c.group_ids?.length));
    } else if (activeGroup !== "all") {
      list = list.filter(c => (c.group_ids || []).includes(activeGroup));
    }
    const needle = q.trim().toLowerCase();
    if (needle) list = list.filter(c =>
      [c.phone, c.name, ...(Object.values(c.extras || {}))]
        .filter(Boolean).some(v => String(v).toLowerCase().includes(needle)));
    return list;
  }, [contacts, activeGroup, q]);

  const allExtras = useMemo(() => {
    const s = new Set();
    contacts.forEach(c => Object.keys(c.extras || {}).forEach(k => s.add(k)));
    return Array.from(s);
  }, [contacts]);

  const parseExtras = (text) => {
    if (!text.trim()) return {};
    const out = {};
    text.split(/\n|;/).forEach(line => {
      const idx = line.indexOf(":");
      if (idx < 0) return;
      const k = line.slice(0, idx).trim().replace(/\s+/g, "_").toLowerCase();
      const v = line.slice(idx + 1).trim();
      if (k) out[k] = v;
    });
    return out;
  };

  const add = async (e) => {
    e.preventDefault();
    try {
      await http.post("/contacts", {
        phone: form.phone,
        name: form.name,
        extras: parseExtras(form.extras_text),
        group_ids: form.group_ids,
      });
      setAddOpen(false);
      setForm({ phone: "", name: "", extras_text: "", group_ids: [] });
      load();
      toast.success("Contact added");
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const del = async (id) => {
    if (!window.confirm("Remove this contact?")) return;
    await http.delete(`/contacts/${id}`);
    load();
  };

  const saveGroup = async (e) => {
    e.preventDefault();
    try {
      if (editGroup) {
        await http.put(`/contacts/groups/${editGroup.id}`, groupForm);
        toast.success("Group updated");
      } else {
        await http.post("/contacts/groups", groupForm);
        toast.success("Group created");
      }
      setGroupOpen(false); setEditGroup(null);
      setGroupForm({ name: "", description: "" });
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const delGroup = async (g) => {
    if (!window.confirm(`Delete group "${g.name}"? Contacts stay in place.`)) return;
    await http.delete(`/contacts/groups/${g.id}`);
    if (activeGroup === g.id) setActiveGroup("all");
    load();
  };

  const bulkAssign = async (gid, action) => {
    if (selected.size === 0) return toast.error("Select contacts first");
    try {
      const r = await http.post(`/contacts/groups/${gid}/bulk`,
        { contact_ids: Array.from(selected), action });
      toast.success(`Updated ${r.data.modified} contact${r.data.modified === 1 ? "" : "s"}`);
      setSelected(new Set()); load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const toggleSel = (id) => {
    const s = new Set(selected);
    if (s.has(id)) s.delete(id); else s.add(id);
    setSelected(s);
  };
  const toggleAll = () => {
    if (selected.size === filtered.length) setSelected(new Set());
    else setSelected(new Set(filtered.map(c => c.id)));
  };

  const countOf = (gid) => contacts.filter(c => (c.group_ids || []).includes(gid)).length;

  return (
    <div>
      <PageHeader
        overline="Address book"
        title="Contacts"
        desc="Organise recipients into groups and add custom fields (name, amount owed, due date) to personalise your messages."
        actions={
          <div className="flex gap-2">
            <Btn variant="ghost" onClick={() => { setEditGroup(null); setGroupForm({ name: "", description: "" }); setGroupOpen(true); }} data-testid="new-group-btn">
              <FolderPlus className="h-4 w-4"/>New group
            </Btn>
            <Btn onClick={() => setAddOpen(true)} data-testid="add-contact-btn">
              <UserPlus className="h-4 w-4"/>Add contact
            </Btn>
          </div>
        }
      />

      <div className="mb-4 grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
        <Stat label="Total contacts" value={contacts.length} accent="white"/>
        <Stat label="Groups"          value={groups.length}/>
        <Stat label="Custom fields"   value={allExtras.length}/>
        <Stat label="Selected now"    value={selected.size} accent={selected.size > 0 ? "green" : "white"}/>
      </div>

      <div className="grid gap-0 border border-zinc-900 lg:grid-cols-[240px,1fr]">
        {/* Group rail */}
        <div className="border-b border-zinc-900 lg:border-b-0 lg:border-r" data-testid="group-rail">
          <GroupRailItem
            label="All contacts" count={contacts.length} icon={Users}
            active={activeGroup === "all"} onClick={() => setActiveGroup("all")}/>
          <GroupRailItem
            label="Ungrouped"
            count={contacts.filter(c => !(c.group_ids?.length)).length}
            icon={Folder}
            active={activeGroup === "ungrouped"} onClick={() => setActiveGroup("ungrouped")}/>
          <div className="mt-2 border-t border-zinc-900 px-4 pt-3 pb-1 font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-600">
            Groups
          </div>
          {groups.length === 0 && (
            <div className="px-4 pb-3 text-xs text-zinc-500">No groups yet.</div>
          )}
          {groups.map(g => (
            <div key={g.id} className="group/item flex items-center">
              <GroupRailItem
                label={g.name} count={countOf(g.id)} icon={Folder}
                active={activeGroup === g.id} onClick={() => setActiveGroup(g.id)}
                testid={`group-${g.id}`}/>
              <div className="flex pr-3 opacity-0 transition group-hover/item:opacity-100">
                <button onClick={(e) => { e.stopPropagation(); setStatsFor(g); }}
                        className="p-1 text-zinc-500 hover:text-blue-400" data-testid={`stats-group-${g.id}`} title="Send stats"><BarChart3 className="h-3 w-3"/></button>
                <button onClick={() => { setEditGroup(g); setGroupForm({ name: g.name, description: g.description || "" }); setGroupOpen(true); }}
                        className="p-1 text-zinc-500 hover:text-white" data-testid={`edit-group-${g.id}`}><Edit className="h-3 w-3"/></button>
                <button onClick={() => delGroup(g)}
                        className="p-1 text-zinc-500 hover:text-red-400" data-testid={`del-group-${g.id}`}><Trash2 className="h-3 w-3"/></button>
              </div>
            </div>
          ))}
        </div>

        {/* Contacts table */}
        <div>
          <div className="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-900 bg-[#141414] px-3 py-2">
            <div className="relative max-w-sm flex-1">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" strokeWidth={1.5}/>
              <Input placeholder="Search phone, name, fields..." value={q}
                      onChange={(e) => setQ(e.target.value)} className="pl-9"
                      data-testid="contact-search"/>
            </div>
            {selected.size > 0 && (
              <div className="flex items-center gap-2 text-xs text-zinc-400" data-testid="bulk-bar">
                {selected.size} selected →
                <select onChange={(e) => e.target.value && bulkAssign(e.target.value, "add")}
                         defaultValue=""
                         className="border border-zinc-700 bg-[#0e0e0e] px-2 py-1 text-xs text-white"
                         data-testid="bulk-add-to-group">
                  <option value="">Add to group...</option>
                  {groups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}
                </select>
                <select onChange={(e) => e.target.value && bulkAssign(e.target.value, "remove")}
                         defaultValue=""
                         className="border border-zinc-700 bg-[#0e0e0e] px-2 py-1 text-xs text-white"
                         data-testid="bulk-remove-from-group">
                  <option value="">Remove from group...</option>
                  {groups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}
                </select>
              </div>
            )}
          </div>

          <Table
            testid="contacts-table"
            rows={filtered}
            empty={q ? "No contacts match your search." : "No contacts here yet."}
            columns={[
              {
                key: "sel", label: (
                  <input type="checkbox" onChange={toggleAll}
                          checked={filtered.length > 0 && selected.size === filtered.length}
                          data-testid="select-all"/>
                ),
                render: r => (
                  <input type="checkbox" checked={selected.has(r.id)}
                          onChange={() => toggleSel(r.id)}
                          data-testid={`sel-${r.id}`}/>
                ),
              },
              { key: "name", label: "Name", render: r => r.name || "—" },
              { key: "phone", label: "Phone", mono: true },
              {
                key: "groups", label: "Groups",
                render: r => (
                  <div className="flex flex-wrap gap-1">
                    {(r.group_ids || []).map(gid => {
                      const g = groups.find(x => x.id === gid);
                      return g ? <Pill key={gid} status="info">{g.name}</Pill> : null;
                    })}
                    {!(r.group_ids?.length) && <span className="text-xs text-zinc-600">—</span>}
                  </div>
                ),
              },
              {
                key: "extras", label: "Custom fields",
                render: r => Object.keys(r.extras || {}).length
                  ? <span className="font-mono text-[11px] text-zinc-400">
                      {Object.entries(r.extras).slice(0, 3).map(([k, v]) => `${k}=${v}`).join(" · ")}
                    </span>
                  : <span className="text-xs text-zinc-600">—</span>,
              },
              {
                key: "actions", label: "",
                render: r => (
                  <button onClick={() => del(r.id)} className="text-zinc-500 hover:text-red-400"
                           data-testid={`del-contact-${r.id}`}>
                    <Trash2 className="h-4 w-4"/>
                  </button>
                ),
              },
            ]}
          />
        </div>
      </div>

      {/* Add contact modal */}
      <Modal open={addOpen} onClose={() => setAddOpen(false)} title="Add contact" testid="add-contact-modal">
        <form onSubmit={add} className="space-y-3">
          <Field label="Phone">
            <Input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })}
                   placeholder="+255712345678" required data-testid="contact-phone"/>
          </Field>
          <Field label="Name">
            <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })}
                   placeholder="Jane Doe" data-testid="contact-name"/>
          </Field>
          <Field label="Custom fields" hint="One per line: key: value. These become personalisation variables.">
            <TextArea value={form.extras_text}
                       onChange={(e) => setForm({ ...form, extras_text: e.target.value })}
                       placeholder="amount_owed: 15000&#10;due_date: 2026-05-01&#10;account_number: 12345"
                       className="min-h-[100px] font-mono text-xs"
                       data-testid="contact-extras"/>
          </Field>
          {groups.length > 0 && (
            <Field label="Groups">
              <div className="flex flex-wrap gap-1">
                {groups.map(g => {
                  const on = form.group_ids.includes(g.id);
                  return (
                    <button type="button" key={g.id}
                             onClick={() => setForm({
                               ...form,
                               group_ids: on ? form.group_ids.filter(x => x !== g.id)
                                              : [...form.group_ids, g.id],
                             })}
                             className={`border px-2 py-1 text-xs transition ${
                               on ? "border-white bg-white text-black"
                                  : "border-zinc-800 text-zinc-400 hover:border-zinc-700"
                             }`}>
                      {on && <Check className="mr-1 inline h-3 w-3"/>}{g.name}
                    </button>
                  );
                })}
              </div>
            </Field>
          )}
          <Btn type="submit" className="w-full" data-testid="contact-save">Save contact</Btn>
        </form>
      </Modal>

      {/* Group modal */}
      <Modal open={groupOpen} onClose={() => { setGroupOpen(false); setEditGroup(null); }}
             title={editGroup ? "Rename group" : "Create group"} testid="group-modal">
        <form onSubmit={saveGroup} className="space-y-3">
          <Field label="Group name">
            <Input value={groupForm.name} onChange={(e) => setGroupForm({ ...groupForm, name: e.target.value })}
                   placeholder="VIP clients, monthly dues..." required data-testid="group-name"/>
          </Field>
          <Field label="Description">
            <Input value={groupForm.description}
                   onChange={(e) => setGroupForm({ ...groupForm, description: e.target.value })}
                   placeholder="Optional"/>
          </Field>
          <Btn type="submit" className="w-full" data-testid="group-save">
            {editGroup ? "Save changes" : "Create group"}
          </Btn>
        </form>
      </Modal>

      <GroupStatsModal group={statsFor} onClose={() => setStatsFor(null)} />
    </div>
  );
}

function GroupStatsModal({ group, onClose }) {
  const [data, setData] = useState(null);
  useEffect(() => {
    if (!group) { setData(null); return; }
    setData(null);
    http.get(`/contacts/groups/${group.id}/stats`)
      .then(r => setData(r.data))
      .catch(() => setData({ error: true }));
  }, [group?.id]);
  if (!group) return null;
  return (
    <Modal open={!!group} onClose={onClose} title={`Send stats — ${group.name}`} testid="group-stats-modal">
      {!data ? (
        <div className="py-8 text-center text-sm text-zinc-500">Crunching numbers…</div>
      ) : data.error ? (
        <div className="py-8 text-center text-sm text-red-400">Could not load stats.</div>
      ) : (
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-3">
            <MiniStat label="Contacts in group" value={data.contact_count.toLocaleString()} />
            <MiniStat label="Lifetime sends"    value={data.lifetime_sends.toLocaleString()} />
            <MiniStat label="Delivered"         value={data.delivered.toLocaleString()} accent="green"/>
            <MiniStat label="Failed"            value={data.failed.toLocaleString()} accent={data.failed > 0 ? "red" : "default"}/>
          </div>
          <div className="rounded-md border border-blue-500/20 bg-blue-500/[0.04] p-3 text-sm">
            <div className="text-[10px] uppercase tracking-wider text-blue-300">Delivery rate</div>
            <div className="mt-1 font-mono text-3xl font-medium text-zinc-100">{data.delivery_rate}%</div>
            {data.last_send_at && (
              <div className="mt-1 text-[11px] text-zinc-500">
                Last send: {new Date(data.last_send_at).toLocaleString()}
              </div>
            )}
          </div>
          {data.top_failure_reasons.length > 0 && (
            <div>
              <div className="label-overline mb-2">Top failure reasons</div>
              <div className="space-y-1.5">
                {data.top_failure_reasons.map((r, i) => (
                  <div key={i} className="flex items-center justify-between rounded border border-zinc-800/80 bg-zinc-950/30 px-3 py-2 text-xs">
                    <span className="text-zinc-200">{r.label}</span>
                    <span className="font-mono text-zinc-400">{r.count.toLocaleString()}</span>
                  </div>
                ))}
              </div>
            </div>
          )}
          {data.lifetime_sends === 0 && (
            <div className="rounded-md border border-dashed border-zinc-800 px-4 py-6 text-center text-sm text-zinc-500">
              No messages have been sent to anyone in this group yet.
            </div>
          )}
        </div>
      )}
    </Modal>
  );
}

function MiniStat({ label, value, accent = "default" }) {
  const acc =
    accent === "green" ? "text-emerald-400" :
    accent === "red"   ? "text-red-400"     : "text-zinc-100";
  return (
    <div className="rounded-md border border-zinc-800/80 bg-zinc-950/30 p-3">
      <div className="text-[10px] uppercase tracking-wider text-zinc-500">{label}</div>
      <div className={`mt-1 font-mono text-xl font-medium ${acc}`}>{value}</div>
    </div>
  );
}

function GroupRailItem({ label, count, icon: Icon, active, onClick, testid }) {
  return (
    <button onClick={onClick} data-testid={testid}
            className={`group flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition-colors ${
              active ? "bg-white/5 text-white" : "text-zinc-400 hover:bg-white/5 hover:text-white"
            }`}>
      <Icon className="h-4 w-4 shrink-0" strokeWidth={1.5}/>
      <span className="flex-1 truncate">{label}</span>
      <span className="font-mono text-[10px] text-zinc-500">{count}</span>
    </button>
  );
}
