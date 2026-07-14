import { useMemo, useState } from 'react';
import { createCampusService, deleteCampusService, downloadAttendanceReport, openStudentTranscript, replaceCampusService } from '../services/api';
import { EmptyState } from './Feedback';

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const YEAR = new Date().getFullYear();
const blankSchedule = { course_id: '', semester: 'Spring', year: YEAR, section: '', day_of_week: 0, starts_at: '09:00', ends_at: '10:30', room: '', class_type: 'lecture' };
const blankExam = { course_id: '', semester: 'Spring', year: YEAR, section: '', exam_type: 'Midterm', exam_date: '', starts_at: '10:00', ends_at: '12:00', room: '' };

const time = (value) => String(value || '').slice(0, 5);

export default function RoutineWorkspace({ user, data, academic, reload, setFeedback }) {
  const isAdmin = user?.role === 'admin';
  const [mode, setMode] = useState('class');
  const [schedule, setSchedule] = useState(blankSchedule);
  const [exam, setExam] = useState(blankExam);
  const [editing, setEditing] = useState(null);
  const [busy, setBusy] = useState(false);
  const [filters, setFilters] = useState({ semester: 'Spring', year: YEAR, department: '', faculty: '', section: '', course: '' });
  const courses = useMemo(() => academic?.courses || [], [academic?.courses]);
  const courseMap = useMemo(() => Object.fromEntries(courses.map((course) => [String(course.id), course])), [courses]);
  const departments = [...new Set(courses.map((course) => course.department).filter(Boolean))];
  const facultyNames = [...new Set(courses.map((course) => course.faculty_name).filter(Boolean))];
  const match = (item) => (!filters.semester || item.semester === filters.semester)
    && (!filters.year || Number(item.year) === Number(filters.year))
    && (!filters.course || String(item.course_id) === filters.course)
    && (!filters.department || courseMap[String(item.course_id)]?.department === filters.department)
    && (!filters.faculty || courseMap[String(item.course_id)]?.faculty_name === filters.faculty)
    && (!filters.section || String(item.section || '').toLowerCase().includes(filters.section.toLowerCase()));
  const schedules = (data?.schedules || []).filter(match);
  const exams = (data?.exams || []).filter(match);

  const save = async () => {
    const resource = mode === 'class' ? 'schedules' : 'exams';
    const payload = mode === 'class' ? schedule : exam;
    setBusy(true);
    try {
      const response = editing ? await replaceCampusService(resource, editing, payload) : await createCampusService(resource, payload);
      setFeedback({ variant: 'success', message: response.data.message });
      setEditing(null); setSchedule(blankSchedule); setExam(blankExam); await reload();
    } catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
    finally { setBusy(false); }
  };
  const edit = (kind, item) => {
    setMode(kind); setEditing(item.id);
    if (kind === 'class') setSchedule({ course_id: item.course_id, semester: item.semester || 'Spring', year: item.year || YEAR, section: item.section || '', day_of_week: item.day_of_week, starts_at: time(item.starts_at), ends_at: time(item.ends_at), room: item.room || '', class_type: item.class_type || 'lecture' });
    else setExam({ course_id: item.course_id, semester: item.semester || 'Spring', year: item.year || YEAR, section: item.section || '', exam_type: item.exam_type, exam_date: item.exam_date, starts_at: time(item.starts_at), ends_at: time(item.ends_at), room: item.room || '' });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  const remove = async (resource, id) => {
    if (!window.confirm('Delete this routine record?')) return;
    try { const response = await deleteCampusService(resource, id); setFeedback({ variant: 'success', message: response.data.message }); await reload(); }
    catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
  };
  const today = new Date().getDay();
  const todayClasses = schedules.filter((item) => Number(item.day_of_week) === today);
  const form = mode === 'class' ? schedule : exam;
  const update = (key, value) => mode === 'class' ? setSchedule((current) => ({ ...current, [key]: value })) : setExam((current) => ({ ...current, [key]: value }));

  const exportFile = async (type) => {
    try { if (type === 'transcript') await openStudentTranscript(); else await downloadAttendanceReport(); }
    catch (error) { setFeedback({ variant: 'danger', message: error.message }); }
  };

  return <div className="routine-workspace">
    {isAdmin && <section className="faculty-panel routine-editor no-print">
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><div><span className="eyebrow-label">Routine control</span><h4 className="mb-0">{editing ? 'Edit' : 'Publish'} {mode === 'class' ? 'Class' : 'Exam'} Routine</h4></div><div className="btn-group"><button type="button" className={`btn ${mode === 'class' ? 'btn-primary' : 'btn-outline-primary'}`} onClick={() => { setMode('class'); setEditing(null); }}>Class</button><button type="button" className={`btn ${mode === 'exam' ? 'btn-primary' : 'btn-outline-primary'}`} onClick={() => { setMode('exam'); setEditing(null); }}>Exam</button></div></div>
      <div className="row g-3">
        <div className="col-lg-4"><label className="form-label">Course</label><select className="form-select" value={form.course_id} onChange={(e) => update('course_id', Number(e.target.value))}><option value="">Select course</option>{courses.map((course) => <option key={course.id} value={course.id}>{course.course_code} - {course.title}</option>)}</select></div>
        <div className="col-6 col-lg-2"><label className="form-label">Semester</label><select className="form-select" value={form.semester} onChange={(e) => update('semester', e.target.value)}><option>Spring</option><option>Fall</option></select></div>
        <div className="col-6 col-lg-2"><label className="form-label">Year</label><input type="number" className="form-control" value={form.year} onChange={(e) => update('year', Number(e.target.value))} /></div>
        <div className="col-lg-2"><label className="form-label">Section/Batch</label><input className="form-control" value={form.section} onChange={(e) => update('section', e.target.value)} placeholder="8A" /></div>
        {mode === 'class' ? <><div className="col-lg-2"><label className="form-label">Day</label><select className="form-select" value={form.day_of_week} onChange={(e) => update('day_of_week', Number(e.target.value))}>{DAYS.map((day, index) => <option value={index} key={day}>{day}</option>)}</select></div><div className="col-lg-2"><label className="form-label">Class type</label><select className="form-select" value={form.class_type} onChange={(e) => update('class_type', e.target.value)}><option value="lecture">Lecture</option><option value="lab">Lab</option></select></div></> : <><div className="col-lg-2"><label className="form-label">Exam type</label><input className="form-control" value={form.exam_type} onChange={(e) => update('exam_type', e.target.value)} /></div><div className="col-lg-2"><label className="form-label">Exam date</label><input type="date" className="form-control" value={form.exam_date} onChange={(e) => update('exam_date', e.target.value)} /></div></>}
        <div className="col-6 col-lg-2"><label className="form-label">Starts</label><input type="time" className="form-control" value={form.starts_at} onChange={(e) => update('starts_at', e.target.value)} /></div>
        <div className="col-6 col-lg-2"><label className="form-label">Ends</label><input type="time" className="form-control" value={form.ends_at} onChange={(e) => update('ends_at', e.target.value)} /></div>
        <div className="col-lg-2"><label className="form-label">Room</label><input className="form-control" value={form.room} onChange={(e) => update('room', e.target.value)} placeholder="Room 405" /></div>
      </div>
      <div className="d-flex gap-2 mt-3"><button type="button" className="btn btn-primary" disabled={busy} onClick={save}>{busy ? 'Saving...' : editing ? 'Update Routine' : 'Publish Routine'}</button>{editing && <button type="button" className="btn btn-outline-secondary" onClick={() => { setEditing(null); setSchedule(blankSchedule); setExam(blankExam); }}>Cancel</button>}</div>
    </section>}

    <section className="faculty-panel routine-filters no-print"><div className="row g-3 align-items-end"><div className="col-md-3"><label className="form-label">Semester</label><select className="form-select" value={filters.semester} onChange={(e) => setFilters({ ...filters, semester: e.target.value })}><option value="">All</option><option>Spring</option><option>Fall</option></select></div><div className="col-md-2"><label className="form-label">Year</label><input type="number" className="form-control" value={filters.year} onChange={(e) => setFilters({ ...filters, year: e.target.value })} /></div><div className="col-md-3"><label className="form-label">Department</label><select className="form-select" value={filters.department} onChange={(e) => setFilters({ ...filters, department: e.target.value })}><option value="">All departments</option>{departments.map((item) => <option key={item}>{item}</option>)}</select></div><div className="col-md-2"><label className="form-label">Faculty</label><select className="form-select" value={filters.faculty} onChange={(e) => setFilters({ ...filters, faculty: e.target.value })}><option value="">All faculty</option>{facultyNames.map((name) => <option key={name}>{name}</option>)}</select></div><div className="col-md-2"><label className="form-label">Section</label><input className="form-control" value={filters.section} onChange={(e) => setFilters({ ...filters, section: e.target.value })} placeholder="All" /></div><div className="col-md-2"><label className="form-label">Course</label><select className="form-select" value={filters.course} onChange={(e) => setFilters({ ...filters, course: e.target.value })}><option value="">All courses</option>{courses.map((course) => <option value={String(course.id)} key={course.id}>{course.course_code}</option>)}</select></div><div className="col-md-1"><button className="btn btn-outline-primary w-100" type="button" onClick={() => window.print()}>Print</button></div></div></section>

    {user?.role === 'student' && <div className="d-flex flex-wrap gap-2 mb-4 no-print"><button className="btn btn-primary" onClick={() => exportFile('transcript')}>Open Transcript / PDF</button><button className="btn btn-outline-success" onClick={() => exportFile('attendance')}>Download Attendance CSV</button></div>}
    {user?.role === 'student' && <section className="today-classes mb-4"><div><span className="eyebrow-label">Today</span><h4>{DAYS[today]}'s Classes</h4></div>{todayClasses.length ? <div className="today-class-grid">{todayClasses.map((item) => <article key={item.id}><strong>{item.course_code}</strong><span>{time(item.starts_at)}–{time(item.ends_at)}</span><small>{item.room} · {item.class_type}</small></article>)}</div> : <span className="text-secondary">No classes scheduled for today.</span>}</section>}

    <section className="faculty-panel routine-print-area"><div className="d-flex justify-content-between mb-3"><div><span className="eyebrow-label">Weekly timetable</span><h4>Class Routine</h4></div><span>{filters.semester || 'All'} {filters.year}</span></div>
      {schedules.length ? <div className="weekly-routine-grid">{DAYS.map((day, index) => <div className="routine-day" key={day}><h6>{day}</h6>{schedules.filter((item) => Number(item.day_of_week) === index).map((item) => <article className={`routine-slot routine-${item.class_type}`} key={item.id}><strong>{item.course_code}</strong><span>{time(item.starts_at)}–{time(item.ends_at)}</span><small>{item.room} {item.section ? `· ${item.section}` : ''}</small>{isAdmin && <div className="routine-actions no-print"><button onClick={() => edit('class', item)}>Edit</button><button onClick={() => remove('schedules', item.id)}>Delete</button></div>}</article>)}</div>)}</div> : <EmptyState title="No class routine found" message="Adjust the filters or publish a class routine." />}
    </section>

    <section className="faculty-panel mt-4"><div className="d-flex justify-content-between mb-3"><div><span className="eyebrow-label">Assessment schedule</span><h4>Exam Routine</h4></div><span className="badge text-bg-primary">{exams.length} exams</span></div>{exams.length ? <div className="exam-routine-grid">{exams.map((item) => <article key={item.id}><div className="exam-date"><strong>{new Date(`${item.exam_date}T00:00:00`).getDate()}</strong><span>{new Date(`${item.exam_date}T00:00:00`).toLocaleString('en', { month: 'short' })}</span></div><div><span className="badge text-bg-primary">{item.exam_type}</span><h6>{item.course_code} - {item.course_title}</h6><p>{time(item.starts_at)}–{time(item.ends_at)} · {item.room}{item.section ? ` · ${item.section}` : ''}</p></div>{isAdmin && <div className="routine-actions no-print"><button onClick={() => edit('exam', item)}>Edit</button><button onClick={() => remove('exams', item.id)}>Delete</button></div>}</article>)}</div> : <EmptyState title="No exam routine found" message="Published examinations will appear here." />}</section>

    {(data?.reschedules || []).filter((item) => item.status === 'approved').length > 0 && <section className="faculty-panel mt-4"><h4>Approved Class Changes</h4><div className="campus-service-list">{data.reschedules.filter((item) => item.status === 'approved').map((item) => <article className="service-record border-info" key={item.id}><strong>{item.course_code}: Rescheduled</strong><span>{item.original_date} → {item.new_date} · {time(item.starts_at)}–{time(item.ends_at)} · {item.room || 'Room TBA'}</span></article>)}</div></section>}
  </div>;
}
