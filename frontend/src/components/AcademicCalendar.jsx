import { useMemo, useState } from 'react';
import { createCampusService, deleteCampusService, replaceCampusService } from '../services/api';

const types = ['academic', 'holiday', 'exam', 'registration', 'deadline', 'orientation'];
const EVENT_COLORS = {
  academic: { backgroundColor: '#dbeafe', color: '#1d4ed8', accent: '#2563eb' },
  holiday: { backgroundColor: '#fee2e2', color: '#b91c1c', accent: '#ef4444' },
  exam: { backgroundColor: '#ede9fe', color: '#6d28d9', accent: '#8b5cf6' },
  registration: { backgroundColor: '#dcfce7', color: '#15803d', accent: '#22c55e' },
  deadline: { backgroundColor: '#ffedd5', color: '#c2410c', accent: '#f97316' },
  orientation: { backgroundColor: '#cffafe', color: '#0e7490', accent: '#06b6d4' },
  rescheduled: { backgroundColor: '#ccfbf1', color: '#0f766e', accent: '#14b8a6' },
};
const eventColors = (type) => EVENT_COLORS[String(type || 'academic').toLowerCase()] || EVENT_COLORS.academic;
const blank = { title: '', description: '', starts_on: '', ends_on: '', event_type: 'academic', audience: 'all', is_all_day: true, recurrence: 'none' };
const iso = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

export default function AcademicCalendar({ user, data, reload, setFeedback }) {
  const isAdmin = user?.role === 'admin';
  const [month, setMonth] = useState(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
  const [form, setForm] = useState(blank);
  const [editing, setEditing] = useState(null);
  const [busy, setBusy] = useState(false);
  const year = month.getFullYear(); const monthIndex = month.getMonth();
  const cells = useMemo(() => {
    const first = new Date(year, monthIndex, 1); const start = new Date(year, monthIndex, 1 - first.getDay());
    return Array.from({ length: 42 }, (_, index) => new Date(start.getFullYear(), start.getMonth(), start.getDate() + index));
  }, [monthIndex, year]);
  const calendarEvents = useMemo(() => [
    ...(data?.events || []),
    ...(data?.exams || []).map((item) => ({ id: `exam-${item.id}`, title: `${item.exam_type}: ${item.course_code}`, starts_on: item.exam_date, ends_on: item.exam_date, event_type: 'exam', audience: 'all', virtual: true })),
    ...(data?.reschedules || []).filter((item) => item.status === 'approved').map((item) => ({ id: `reschedule-${item.id}`, title: `Rescheduled: ${item.course_code}`, starts_on: item.new_date, ends_on: item.new_date, event_type: 'rescheduled', audience: 'all', virtual: true })),
  ], [data]);
  const onDate = (event, date) => {
    const target = iso(date); const start = event.starts_on; const end = event.ends_on || start;
    if (target >= start && target <= end) return true;
    const original = new Date(`${start}T00:00:00`);
    if (event.recurrence === 'weekly') return date >= original && date.getDay() === original.getDay();
    if (event.recurrence === 'monthly') return date >= original && date.getDate() === original.getDate();
    if (event.recurrence === 'yearly') return date >= original && date.getDate() === original.getDate() && date.getMonth() === original.getMonth();
    return false;
  };
  const save = async () => {
    setBusy(true);
    try { const response = editing ? await replaceCampusService('events', editing, form) : await createCampusService('events', form); setFeedback({ variant: 'success', message: response.data.message }); setForm(blank); setEditing(null); await reload(); }
    catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
    finally { setBusy(false); }
  };
  const edit = (event) => { setEditing(event.id); setForm({ title: event.title, description: event.description || '', starts_on: event.starts_on, ends_on: event.ends_on || '', event_type: event.event_type, audience: event.audience, is_all_day: Boolean(event.is_all_day), recurrence: event.recurrence || 'none' }); };
  const remove = async (id) => { if (!window.confirm('Delete this academic event?')) return; try { const response = await deleteCampusService('events', id); setFeedback({ variant: 'success', message: response.data.message }); await reload(); } catch (error) { setFeedback({ variant: 'danger', message: error.message }); } };
  const today = iso(new Date());

  return <div className="calendar-workspace">
    {isAdmin && <section className="faculty-panel calendar-editor no-print"><div className="d-flex justify-content-between align-items-center mb-3"><div><span className="eyebrow-label">Calendar control</span><h4>{editing ? 'Edit' : 'Publish'} Academic Event</h4></div>{editing && <button className="btn btn-outline-secondary" onClick={() => { setEditing(null); setForm(blank); }}>Cancel</button>}</div><div className="row g-3"><div className="col-lg-4"><label className="form-label">Title</label><input className="form-control" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} /></div><div className="col-lg-2"><label className="form-label">Type</label><select className="form-select calendar-type-select" style={{ backgroundColor: eventColors(form.event_type).backgroundColor, color: eventColors(form.event_type).color, borderColor: eventColors(form.event_type).accent }} value={form.event_type} onChange={(e) => setForm({ ...form, event_type: e.target.value })}>{types.map((type) => <option key={type} value={type}>{type}</option>)}</select></div><div className="col-lg-2"><label className="form-label">Audience</label><select className="form-select" value={form.audience} onChange={(e) => setForm({ ...form, audience: e.target.value })}><option value="all">Everyone</option><option value="student">Students</option><option value="faculty">Faculty</option><option value="admin">Admin</option></select></div><div className="col-lg-2"><label className="form-label">Starts</label><input type="date" className="form-control" value={form.starts_on} onChange={(e) => setForm({ ...form, starts_on: e.target.value })} /></div><div className="col-lg-2"><label className="form-label">Ends</label><input type="date" className="form-control" value={form.ends_on} onChange={(e) => setForm({ ...form, ends_on: e.target.value })} /></div><div className="col-lg-6"><label className="form-label">Description</label><textarea className="form-control" rows="2" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} /></div><div className="col-lg-3"><label className="form-label">Repeat</label><select className="form-select" value={form.recurrence} onChange={(e) => setForm({ ...form, recurrence: e.target.value })}><option value="none">Does not repeat</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></div><div className="col-lg-3 d-flex align-items-end"><button className="btn btn-primary w-100" disabled={busy} onClick={save}>{busy ? 'Saving...' : editing ? 'Update Event' : 'Publish Event'}</button></div></div></section>}
    <section className="faculty-panel calendar-shell"><div className="calendar-toolbar no-print"><button className="btn btn-outline-secondary" onClick={() => setMonth(new Date(year, monthIndex - 1, 1))}>Previous</button><div><span className="eyebrow-label">Academic calendar</span><h3>{month.toLocaleString('en', { month: 'long', year: 'numeric' })}</h3></div><div className="d-flex gap-2"><button className="btn btn-outline-secondary" onClick={() => setMonth(new Date(new Date().getFullYear(), new Date().getMonth(), 1))}>Today</button><button className="btn btn-outline-primary" onClick={() => window.print()}>Print</button><button className="btn btn-outline-secondary" onClick={() => setMonth(new Date(year, monthIndex + 1, 1))}>Next</button></div></div><div className="calendar-weekdays">{['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((day) => <strong key={day}>{day}</strong>)}</div><div className="academic-month-grid">{cells.map((date) => { const dateKey = iso(date); const events = calendarEvents.filter((event) => onDate(event, date)); return <div key={dateKey} className={`calendar-day${date.getMonth() !== monthIndex ? ' is-outside' : ''}${dateKey === today ? ' is-today' : ''}`}><span className="calendar-date">{date.getDate()}</span><div className="calendar-events">{events.slice(0, 4).map((event) => <div title={event.description || event.title} className={`calendar-event event-${event.event_type}`} style={{ backgroundColor: eventColors(event.event_type).backgroundColor, color: eventColors(event.event_type).color, borderLeft: `3px solid ${eventColors(event.event_type).accent}` }} key={event.id}><span>{event.title}</span>{isAdmin && !event.virtual && <div className="calendar-event-actions no-print"><button onClick={() => edit(event)}>Edit</button><button onClick={() => remove(event.id)}>×</button></div>}</div>)}{events.length > 4 && <small>+{events.length - 4} more</small>}</div></div>; })}</div><div className="calendar-legend">{[...types, 'rescheduled'].map((type) => <span key={type}><i className={`event-${type}`} style={{ backgroundColor: eventColors(type).accent }} />{type}</span>)}</div></section>
  </div>;
}
