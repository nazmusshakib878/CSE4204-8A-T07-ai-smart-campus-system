import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import { EmptyState, LoadingState, StatusAlert } from '../components/Feedback';
import { getFacultyDashboard } from '../services/api';
const icons={students:'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm11 10v-2a3 3 0 0 0-2-2.83M16 3.13a4 4 0 0 1 0 7.75',calendar:'M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z',attendance:'m5 12 4 4L19 6',alert:'M12 3 22 20H2L12 3Zm0 6v5m0 3h.01',arrow:'m9 18 6-6-6-6'};
function FacultyIcon({name}){return <svg viewBox="0 0 24 24" aria-hidden="true"><path d={icons[name]}/></svg>;}
function FacultyDashboardPage(){
 const [data,setData]=useState(null);const [loading,setLoading]=useState(true);const [error,setError]=useState('');
 useEffect(()=>{let active=true;getFacultyDashboard().then(r=>{if(active)setData(r.data.data);}).catch(e=>{if(active)setError(e.message);}).finally(()=>{if(active)setLoading(false);});return()=>{active=false;};},[]);
 const stats=useMemo(()=>data?[
  {label:'Total Students',value:data.stats.total_students,detail:`Across ${data.stats.total_courses} courses`,tone:'blue',icon:'students'},
  {label:'Classes This Week',value:data.stats.classes_this_week,detail:'Configured weekly classes',tone:'green',icon:'calendar'},
  {label:'Avg. Attendance',value:`${data.stats.average_attendance}%`,detail:'Recorded attendance',tone:'amber',icon:'attendance'},
  {label:'At-Risk Students',value:data.stats.at_risk_students,detail:'High-priority alerts',tone:'red',icon:'alert'},
 ]:[],[data]);
 const next=data?.schedule?.[0];
 return <Layout title="Faculty Dashboard" subtitle={data?`${data.faculty.name}  |  ${data.faculty.designation||'Faculty'}  |  ${data.faculty.department||'Department not set'}`:'Faculty workspace'}>
  <section className="faculty-dashboard-hero mb-4"><div><span className="faculty-hero-eyebrow">TEACHING WORKSPACE</span><h2>{data?`Welcome back, ${data.faculty.name}`:'Faculty dashboard'}</h2><p>Live course, attendance and academic performance data.</p></div>{next&&<div className="faculty-next-class"><span>NEXT SCHEDULED CLASS</span><strong>{next.title}</strong><small>{next.day}  |  {next.starts_at}  |  {next.room||'Room not set'}</small></div>}</section>
  {error&&<StatusAlert variant="danger" message={error}/>}
  {loading?<LoadingState message="Loading faculty dashboard..."/>:data&&<>
   <div className="row g-4 mb-4">{stats.map(i=><div key={i.label} className="col-sm-6 col-xl-3"><article className={`faculty-kpi-card faculty-kpi-${i.tone}`}><div className="faculty-kpi-top"><span className="faculty-kpi-icon"><FacultyIcon name={i.icon}/></span></div><span className="faculty-kpi-label">{i.label}</span><strong>{i.value}</strong><small>{i.detail}</small></article></div>)}</div>
   <div className="row g-4 mb-4"><div className="col-xl-8"><section className="faculty-dashboard-panel h-100"><div className="faculty-panel-heading"><div><span className="faculty-panel-eyebrow">WEEKLY AGENDA</span><h3>Class schedule</h3><p>Classes configured for your assigned courses</p></div><span className="faculty-week-pill">Weekly</span></div>{data.schedule.length?<div className="faculty-schedule-list faculty-schedule-polished">{data.schedule.map(i=><article key={i.id} className="faculty-schedule-item"><span className="faculty-day-badge">{i.day}</span><div className="faculty-class-copy"><span>{i.code}</span><strong>{i.title}</strong><small>{i.starts_at} - {i.ends_at}  |  {i.room||'Room not set'}  |  {i.class_type}</small></div><span className="faculty-student-count">{i.students} students</span></article>)}</div>:<EmptyState title="No class schedule configured" message="Schedule records added for assigned courses will appear here."/>}</section></div>
   <div className="col-xl-4"><section className="faculty-dashboard-panel h-100"><div className="faculty-panel-heading mb-4"><div><span className="faculty-panel-eyebrow">ACADEMIC HEALTH</span><h3>Course performance</h3><p>Grades and attendance from academic records</p></div></div>{data.performance.length?<div className="faculty-progress-list faculty-progress-polished">{data.performance.map((i,index)=>{const value=i.average_score??i.attendance_percentage??0;return <div key={i.course_id}><div className="d-flex justify-content-between gap-3 mb-2"><span>{i.label}</span><strong>{i.average_score===null?'No grades':`${i.average_score}/100`}</strong></div><div className={`faculty-progress-track progress-${['blue','violet','green','amber'][index%4]}`}><span style={{width:`${value}%`}}/></div><small className="text-secondary">{i.attendance_percentage===null?'No attendance recorded':`${i.attendance_percentage}% attendance`}</small></div>;})}</div>:<EmptyState title="No course performance yet" message="Grades and attendance entries will appear here."/>}</section></div></div>
   <section className="faculty-dashboard-panel"><div className="faculty-panel-heading mb-3"><div><span className="faculty-panel-eyebrow">QUICK ACCESS</span><h3>Student success tools</h3></div></div><div className="row g-3">{[['/student-monitoring','Student monitoring','Attendance, CGPA and activity','students'],['/risk-alerts','Risk alerts','Review students needing attention','alert'],['/notices/manage','Send notices','Publish academic communication','calendar']].map(([to,title,detail,icon])=><div key={to} className="col-md-4"><Link to={to} className="faculty-quick-link"><span className="faculty-quick-icon"><FacultyIcon name={icon}/></span><span><strong>{title}</strong><small>{detail}</small></span><FacultyIcon name="arrow"/></Link></div>)}</div></section>
  </>}
 </Layout>;
}
export default FacultyDashboardPage;
