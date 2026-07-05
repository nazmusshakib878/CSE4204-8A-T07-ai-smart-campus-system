import { useCallback, useEffect, useMemo, useState } from 'react';
import Layout from '../components/Layout';
import { useAuth } from '../auth/auth-context';
import { EmptyState, LoadingState, StatusAlert } from '../components/Feedback';
import { createTask, getRecommendations } from '../services/api';

const fallbackCourses = [
  {
    id: 'cse-5101',
    code: 'CSE 5101',
    title: 'Machine Learning',
    description: 'Strong AI coursework and database performance make this a strong fit.',
    level: 'Advanced',
    credits: 3,
    faculty: 'Dr. Rahman',
    prerequisites: ['CSE 4103', 'MATH 3201'],
    score: 96,
  },
  {
    id: 'cse-5103',
    code: 'CSE 5103',
    title: 'Cloud Computing',
    description: 'Matches your network, systems, and backend development profile.',
    level: 'Intermediate',
    credits: 3,
    faculty: 'Dr. Karim',
    prerequisites: ['CSE 4105'],
    score: 91,
  },
  {
    id: 'cse-5201',
    code: 'CSE 5201',
    title: 'Distributed Systems',
    description: 'Complements database, operating systems, and software architecture skills.',
    level: 'Advanced',
    credits: 3,
    faculty: 'Dr. Islam',
    prerequisites: ['CSE 4107', 'CSE 4205'],
    score: 88,
  },
  {
    id: 'mgt-3101',
    code: 'MGT 3101',
    title: 'Tech Entrepreneurship',
    description: 'Broadens your engineering profile with startup and product thinking.',
    level: 'Moderate',
    credits: 2,
    faculty: 'Prof. Hossain',
    prerequisites: ['None'],
    score: 82,
  },
];

const levelTone = {
  Advanced: 'danger',
  Intermediate: 'warning',
  Moderate: 'success',
};

const parseRecommendation = (item, index) => {
  const titleParts = String(item.title || '').split('|').map((part) => part.trim());
  const [code, courseTitle] = titleParts.length > 1 ? titleParts : [`REC ${index + 1}`, item.title || 'Recommended Course'];

  return {
    id: item.id || `recommendation-${index}`,
    code,
    title: courseTitle,
    description: item.description || 'Recommended from your academic profile.',
    level: item.recommendation_type || 'Recommended',
    credits: 3,
    faculty: item.target_user || 'Academic Advisor',
    prerequisites: ['Advisor review'],
    score: Math.round(Number(item.score || 0)) || 80,
  };
};

function CourseRecommendationsPage() {
  const { user } = useAuth();
  const [recommendations, setRecommendations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [addingCourseId, setAddingCourseId] = useState('');
  const [addedCourses, setAddedCourses] = useState([]);

  const fetchRecommendations = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const response = await getRecommendations();
      setRecommendations(response.data.data || []);
    } catch (requestError) {
      setError(requestError.message || 'Course recommendations could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchRecommendations();
  }, [fetchRecommendations]);

  const courses = useMemo(() => {
    if (!recommendations.length) return fallbackCourses;
    return recommendations.map(parseRecommendation);
  }, [recommendations]);

  const handleAddCourse = async (course) => {
    setAddingCourseId(course.id);
    setFeedback(null);

    try {
      await createTask({
        title: `Add ${course.code} - ${course.title} to next semester`,
        description: `Recommended course with ${course.score}% match. Prerequisites: ${course.prerequisites.join(', ')}.`,
        assigned_to: user?.name || '',
        due_date: '',
        status: 'pending',
        priority: 'medium',
      });

      setAddedCourses((currentCourses) => [...new Set([...currentCourses, course.id])]);
      setFeedback({
        variant: 'success',
        message: `${course.title} was added as a next-semester planning task.`,
      });
    } catch (requestError) {
      setFeedback({
        variant: 'danger',
        message: requestError.message || 'This course could not be added right now.',
      });
    } finally {
      setAddingCourseId('');
    }
  };

  if (loading) {
    return (
      <Layout title="Course Recommendations" subtitle="AI-generated study path based on your academic profile.">
        <LoadingState message="Loading course recommendations..." />
      </Layout>
    );
  }

  return (
    <Layout title="Course Recommendations" subtitle="AI-generated study path based on your academic profile.">
      {error && (
        <StatusAlert
          variant="warning"
          message={`${error} Showing sample recommendations for preview.`}
          actionLabel="Try again"
          onAction={fetchRecommendations}
          onDismiss={() => setError('')}
        />
      )}
      {feedback && (
        <StatusAlert
          variant={feedback.variant}
          message={feedback.message}
          onDismiss={() => setFeedback(null)}
        />
      )}

      <section className="recommendation-analysis mb-4">
        <div className="recommendation-analysis-icon" aria-hidden="true">AI</div>
        <div>
          <h5>AI Analysis for {user?.name || 'Campus User'}</h5>
          <p>
            Based on your academic profile, performance signals, and active campus work,
            here are the recommended courses for your next semester.
          </p>
        </div>
      </section>

      {courses.length > 0 ? (
        <div className="row g-4">
          {courses.map((course) => {
            const isAdded = addedCourses.includes(course.id);
            const isAdding = addingCourseId === course.id;

            return (
              <div key={course.id} className="col-xl-6">
                <article className="course-recommendation-card h-100">
                  <div className="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div className="min-w-0">
                      <span className="course-code">{course.code}</span>
                      <h4>{course.title}</h4>
                    </div>
                    <div className="course-score">
                      <strong>{course.score}%</strong>
                      <span>match</span>
                    </div>
                  </div>

                  <p className="course-reason">{course.description}</p>

                  <div className="course-meta-row">
                    <span className={`course-pill course-pill-${levelTone[course.level] || 'primary'}`}>{course.level}</span>
                    <span className="course-pill course-pill-primary">{course.credits} Credits</span>
                    <span className="course-pill course-pill-violet">{course.faculty}</span>
                  </div>

                  <div className="course-prerequisites">
                    <span>Prerequisites:</span>
                    <div>
                      {course.prerequisites.map((item) => (
                        <small key={item}>{item}</small>
                      ))}
                    </div>
                  </div>

                  <button
                    type="button"
                    className={`btn w-100 ${isAdded ? 'btn-success' : 'btn-outline-primary'}`}
                    onClick={() => handleAddCourse(course)}
                    disabled={isAdding || isAdded}
                    aria-busy={isAdding}
                  >
                    {isAdding && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
                    {isAdded ? 'Added to Next Semester' : isAdding ? 'Adding...' : 'Add to Next Semester'}
                  </button>
                </article>
              </div>
            );
          })}
        </div>
      ) : (
        <EmptyState
          title="No course recommendations yet"
          message="Recommendations will appear here when the API has course data."
        />
      )}
    </Layout>
  );
}

export default CourseRecommendationsPage;