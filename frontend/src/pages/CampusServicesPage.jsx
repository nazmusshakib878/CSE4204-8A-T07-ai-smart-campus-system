import { useCallback, useEffect, useMemo, useState } from 'react';
import Layout from '../components/Layout';
import RoutineWorkspace from '../components/RoutineWorkspace';
import AcademicCalendar from '../components/AcademicCalendar';
import { useAuth } from '../auth/auth-context';
import { EmptyState, LoadingState, StatusAlert } from '../components/Feedback';
import {
  borrowLibraryBook, createCampusService, getAcademicManagement,
  getCampusServices, returnLibraryLoan, updateCampusService,
} from '../services/api';

const YEAR = new Date().getFullYear();
const initialForms = {
  exam: { course_id: '', semester: 'Spring', year: YEAR, exam_type: 'Midterm', exam_date: '', starts_at: '10:00', ends_at: '12:00', room: '' },
  schedule: { course_id: '', semester: 'Spring', year: YEAR, day_of_week: 0, starts_at: '09:00', ends_at: '10:30', room: '', class_type: 'lecture' },
  event: { title: '', description: '', starts_on: '', ends_on: '', event_type: 'academic', audience: 'all' },
  fee: { student_id: '', semester: 'Spring', year: YEAR, amount_due: '', amount_paid: 0, due_date: '', reference: '' },
  ticket: { category: 'Academic', subject: '', description: '', priority: 'medium' },
  leave: { starts_on: '', ends_on: '', reason: '' },
  reschedule: { course_id: '', original_date: '', new_date: '', starts_at: '09:00', ends_at: '10:30', room: '', reason: '' },
  book: { isbn: '', title: '', author: '', category: '', total_copies: 1, shelf: '' },
};

const Field = ({ label, children }) => <label className="form-label fw-semibold">{label}{children}</label>;

function CampusServicesPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const isFaculty = user?.role === 'faculty';
  const isStudent = user?.role === 'student';
  const [tab, setTab] = useState('routines');
  const [data, setData] = useState(null);
  const [academic, setAcademic] = useState({ courses: [], students: [] });
  const [forms, setForms] = useState(initialForms);
  const [loading, setLoading] = useState(true);
  const [, setBusy] = useState('');
  const [feedback, setFeedback] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [services, academics] = await Promise.all([
        getCampusServices(),
        isStudent ? Promise.resolve(null) : getAcademicManagement(),
      ]);
      setData(services.data.data);
      setAcademic(academics?.data?.data || { courses: [], students: [] });
    } catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
    finally { setLoading(false); }
  }, [isStudent]);

  useEffect(() => { load(); }, [load]);
  const setField = (form, field, value) => setForms((current) => ({ ...current, [form]: { ...current[form], [field]: value } }));
  const submit = async (key, resource, payload = forms[key]) => {
    setBusy(key); setFeedback(null);
    try { const response = await createCampusService(resource, payload); setFeedback({ variant: 'success', message: response.data.message }); await load(); }
    catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
    finally { setBusy(''); }
  };
  const update = async (resource, id, payload) => {
    setBusy(`${resource}-${id}`);
    try { const response = await updateCampusService(resource, id, payload); setFeedback({ variant: 'success', message: response.data.message }); await load(); }
    catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
    finally { setBusy(''); }
  };
  const action = async (key, callback) => {
    setBusy(key);
    try { const response = await callback(); setFeedback({ variant: 'success', message: response?.data?.message || 'Completed successfully.' }); await load(); }
    catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
    finally { setBusy(''); }
  };

  const tabs = useMemo(() => [
    ['routines', 'Routines'], ['calendar', 'Academic Calendar'], ['fees', 'Fees'],
    ['library', 'Library'], ['helpdesk', 'Helpdesk'], ...(isStudent ? [] : [['staff', 'Faculty Operations']]),
  ], [isStudent]);

  if (loading && !data) return <Layout title="Campus Services"><LoadingState message="Loading campus services..." /></Layout>;
  const list = (items, empty, render) => items?.length ? <div className="campus-service-list">{items.map(render)}</div> : <EmptyState title={empty} message="New records will appear here automatically." />;

  return <Layout title="Campus Services" subtitle="Routines, fees, library, calendar, support, and faculty operations in one secure workspace.">
    {feedback && <StatusAlert variant={feedback.variant} message={feedback.message} onDismiss={() => setFeedback(null)} />}
    <section className="academic-management-hero mb-4"><div><span>UNIVERSITY OPERATIONS</span><h3>Connected campus service center</h3><p>Role-based services backed by verified university records.</p></div><div className="academic-summary"><strong>{tabs.length}</strong><span>Active services</span></div></section>
    <div className="academic-tabs mb-4">{tabs.map(([key, label]) => <button type="button" key={key} className={tab === key ? 'is-active' : ''} onClick={() => setTab(key)}>{label}</button>)}</div>

    {tab === 'routines' && <RoutineWorkspace user={user} data={data} academic={academic} reload={load} setFeedback={setFeedback} />}

    {tab === 'calendar' && <AcademicCalendar user={user} data={data} reload={load} setFeedback={setFeedback} />}

    {tab === 'fees' && <div className="row g-4">{isAdmin && <div className="col-xl-4"><section className="faculty-panel"><h4>Update Student Fee</h4><Field label="University ID"><select className="form-select" value={forms.fee.student_id} onChange={(e) => setField('fee', 'student_id', e.target.value)}><option value="">Select by University ID</option>{academic.students.map((s) => <option key={s.id} value={s.id}>{s.student_number} — {s.name} ({s.email})</option>)}</select></Field>{[['amount_due', 'Amount due'], ['amount_paid', 'Amount paid']].map(([key, label]) => <Field key={key} label={label}><input type="number" min="0" className="form-control" value={forms.fee[key]} onChange={(e) => setField('fee', key, Number(e.target.value))} /></Field>)}<Field label="Due date"><input type="date" className="form-control" value={forms.fee.due_date} onChange={(e) => setField('fee', 'due_date', e.target.value)} /></Field><button className="btn btn-primary w-100" onClick={() => submit('fee', 'fees')}>Save Fee Status</button></section></div>}<div className={isAdmin ? 'col-xl-8' : 'col-12'}><section className="faculty-panel"><h4>{isStudent ? 'My Fee Status' : 'Student Fee Records'}</h4>{list(data?.fees, 'No fee records', (item) => <article className="service-record" key={item.id}><div><strong>{item.student_number ? `${item.student_number} — ${item.student_name}` : `${item.semester} ${item.year}`}</strong><span className={`badge ms-2 ${item.status === 'paid' ? 'text-bg-success' : item.status === 'partial' ? 'text-bg-warning' : 'text-bg-danger'}`}>{item.status}</span></div><span>Due: ৳{item.amount_due} | Paid: ৳{item.amount_paid} | Balance: ৳{Number(item.amount_due) - Number(item.amount_paid)}</span></article>)}</section></div></div>}

    {tab === 'library' && <div className="row g-4">{isAdmin && <div className="col-xl-4"><section className="faculty-panel"><h4>Add Library Book</h4>{[['title', 'Title'], ['author', 'Author'], ['isbn', 'ISBN'], ['category', 'Category'], ['shelf', 'Shelf']].map(([key, label]) => <Field key={key} label={label}><input className="form-control" value={forms.book[key]} onChange={(e) => setField('book', key, e.target.value)} /></Field>)}<Field label="Copies"><input type="number" min="1" className="form-control" value={forms.book.total_copies} onChange={(e) => setField('book', 'total_copies', Number(e.target.value))} /></Field><button className="btn btn-primary w-100" onClick={() => submit('book', 'books')}>Add Book</button></section></div>}<div className={isAdmin ? 'col-xl-8' : 'col-12'}><section className="faculty-panel"><h4>Book Catalogue</h4>{list(data?.books, 'No books in catalogue', (book) => <article className="service-record" key={book.id}><div><strong>{book.title}</strong><span> by {book.author}</span></div><span>{book.category || 'General'} | Shelf {book.shelf || '-'} | {book.available_copies}/{book.total_copies} available</span>{book.available_copies > 0 && !isAdmin && <button className="btn btn-sm btn-outline-primary" onClick={() => action(`borrow-${book.id}`, () => borrowLibraryBook(book.id))}>Borrow</button>}</article>)}</section><section className="faculty-panel mt-4"><h4>{isAdmin ? 'Active Loans' : 'My Loans'}</h4>{list(data?.loans, 'No library loans', (loan) => <article className="service-record" key={loan.id}><strong>{loan.book_title}</strong><span>{loan.borrower_name} | Due {loan.due_on} | {loan.status}</span>{loan.status !== 'returned' && <button className="btn btn-sm btn-outline-success" onClick={() => action(`return-${loan.id}`, () => returnLibraryLoan(loan.id))}>Return</button>}</article>)}</section></div></div>}

    {tab === 'helpdesk' && <div className="row g-4"><div className="col-xl-4"><section className="faculty-panel"><h4>New Support Ticket</h4><Field label="Category"><select className="form-select" value={forms.ticket.category} onChange={(e) => setField('ticket', 'category', e.target.value)}><option>Academic</option><option>Accounts</option><option>Technical</option><option>Library</option><option>Other</option></select></Field><Field label="Subject"><input className="form-control" value={forms.ticket.subject} onChange={(e) => setField('ticket', 'subject', e.target.value)} /></Field><Field label="Details"><textarea className="form-control" rows="4" value={forms.ticket.description} onChange={(e) => setField('ticket', 'description', e.target.value)} /></Field><button className="btn btn-primary w-100" onClick={() => submit('ticket', 'tickets')}>Submit Ticket</button></section></div><div className="col-xl-8"><section className="faculty-panel"><h4>{isAdmin ? 'All Support Tickets' : 'My Support Tickets'}</h4>{list(data?.tickets, 'No support tickets', (ticket) => <article className="service-record" key={ticket.id}><div><span className="badge text-bg-secondary">{ticket.category}</span> <strong>{ticket.subject}</strong>{ticket.requester_name && <small className="d-block text-secondary">From {ticket.requester_university_id || 'ID unavailable'} · {ticket.requester_name} ({ticket.requester_email})</small>}</div><p>{ticket.description}</p><span>Status: {ticket.status}{ticket.response ? ` | Response: ${ticket.response}` : ''}</span>{isAdmin && ticket.status !== 'resolved' && <button className="btn btn-sm btn-success" onClick={() => update('tickets', ticket.id, { status: 'resolved', response: 'Your request has been reviewed and resolved by the administration.' })}>Resolve</button>}</article>)}</section></div></div>}

    {tab === 'staff' && <div className="row g-4">{isFaculty && <><div className="col-lg-6"><section className="faculty-panel"><h4>Leave Application</h4><Field label="From"><input type="date" className="form-control" value={forms.leave.starts_on} onChange={(e) => setField('leave', 'starts_on', e.target.value)} /></Field><Field label="To"><input type="date" className="form-control" value={forms.leave.ends_on} onChange={(e) => setField('leave', 'ends_on', e.target.value)} /></Field><Field label="Reason"><textarea className="form-control" value={forms.leave.reason} onChange={(e) => setField('leave', 'reason', e.target.value)} /></Field><button className="btn btn-primary" onClick={() => submit('leave', 'leaves')}>Apply</button></section></div><div className="col-lg-6"><section className="faculty-panel"><h4>Class Reschedule</h4><Field label="Course"><select className="form-select" value={forms.reschedule.course_id} onChange={(e) => setField('reschedule', 'course_id', e.target.value)}><option value="">Select</option>{academic.courses.map((c) => <option key={c.id} value={c.id}>{c.course_code}</option>)}</select></Field><div className="row"><div className="col"><Field label="Original"><input type="date" className="form-control" value={forms.reschedule.original_date} onChange={(e) => setField('reschedule', 'original_date', e.target.value)} /></Field></div><div className="col"><Field label="New date"><input type="date" className="form-control" value={forms.reschedule.new_date} onChange={(e) => setField('reschedule', 'new_date', e.target.value)} /></Field></div></div><button className="btn btn-primary" onClick={() => submit('reschedule', 'reschedules')}>Request Reschedule</button></section></div></>}
      <div className="col-12"><section className="faculty-panel"><h4>Leave & Reschedule Status</h4>{list([...(data?.leaves || []).map((x) => ({ ...x, kind: 'leave' })), ...(data?.reschedules || []).map((x) => ({ ...x, kind: 'reschedule' }))], 'No faculty requests', (item) => <article className="service-record" key={`${item.kind}-${item.id}`}><strong>{item.kind === 'leave' ? `${item.faculty_name}: Leave` : `${item.course_code}: Reschedule`}</strong><span>{item.starts_on || item.original_date} to {item.ends_on || item.new_date} | {item.status}</span>{isAdmin && item.status === 'pending' && <div className="d-flex gap-2"><button className="btn btn-sm btn-success" onClick={() => update(item.kind === 'leave' ? 'leaves' : 'reschedules', item.id, { status: 'approved' })}>Approve</button><button className="btn btn-sm btn-outline-danger" onClick={() => update(item.kind === 'leave' ? 'leaves' : 'reschedules', item.id, { status: 'rejected' })}>Reject</button></div>}</article>)}</section></div>
    </div>}
  </Layout>;
}

export default CampusServicesPage;
