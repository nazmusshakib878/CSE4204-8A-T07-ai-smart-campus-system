import { useMemo, useState } from 'react';
import { fall2026Rrf } from '../data/fall2026Rrf';

export default function FallRegistrationReference() {
  const [semester, setSemester] = useState(2);
  const reference = fall2026Rrf[semester];
  const calculatedCredits = useMemo(() => reference.courses.reduce((total, [, , credit]) => total + credit, 0), [reference]);

  return <section className="faculty-panel fall-registration-reference">
    <div className="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div><span className="eyebrow-label text-primary">READ-ONLY COURSE REFERENCE</span><h4 className="mb-1">Fall 2026 CSE Registration Reference</h4><p className="text-secondary mb-0">This is an RRF reference only; it does not register courses or alter academic records.</p></div>
      <button type="button" className="btn btn-outline-primary d-print-none" onClick={() => window.print()}>Print reference</button>
    </div>
    <div className="row g-3 align-items-end mb-4 d-print-none"><div className="col-sm-5"><label className="form-label fw-semibold" htmlFor="rrf-semester">Semester</label><select id="rrf-semester" className="form-select" value={semester} onChange={(event) => setSemester(Number(event.target.value))}>{Object.keys(fall2026Rrf).map((value) => <option key={value} value={value}>{value}{value === '2' ? 'nd' : value === '3' ? 'rd' : 'th'} Semester</option>)}</select></div><div className="col-sm-7"><small className="text-secondary">The supplied Fall 2026 RRF covers semesters 2-8 only. Semester 1 is intentionally unavailable.</small></div></div>
    <div className="d-flex justify-content-between align-items-center mb-3"><strong>Fall 2026 - {semester}{semester === 2 ? 'nd' : semester === 3 ? 'rd' : 'th'} Semester</strong><span className="course-pill course-pill-primary">Total credits: {reference.totalCredits}</span></div>
    <div className="table-responsive"><table className="table align-middle mb-0"><thead><tr><th>Course code</th><th>Course title</th><th className="text-end">Credits</th></tr></thead><tbody>{reference.courses.map(([code, title, credit]) => <tr key={code}><td><strong>{code}</strong></td><td>{title}</td><td className="text-end">{credit}</td></tr>)}</tbody><tfoot><tr><th colSpan="2">Total</th><th className="text-end">{calculatedCredits}</th></tr></tfoot></table></div>
  </section>;
}
