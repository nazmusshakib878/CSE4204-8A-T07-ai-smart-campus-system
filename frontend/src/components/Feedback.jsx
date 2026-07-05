import { useEffect, useRef } from 'react';

export function StatusAlert({
  variant = 'info',
  message,
  actionLabel,
  onAction,
  onDismiss,
}) {
  return (
    <div
      className={`status-alert alert alert-${variant} d-flex align-items-center gap-3`}
      role={variant === 'danger' ? 'alert' : 'status'}
      aria-live={variant === 'danger' ? 'assertive' : 'polite'}
    >
      <div className="flex-grow-1">
        <span>{message}</span>
        {actionLabel && onAction && (
          <button type="button" className="alert-link btn btn-link p-0 ms-2 align-baseline" onClick={onAction}>
            {actionLabel}
          </button>
        )}
      </div>
      {onDismiss && (
        <button
          type="button"
          className="btn-close flex-shrink-0"
          aria-label="Dismiss message"
          onClick={onDismiss}
        />
      )}
    </div>
  );
}

export function LoadingState({ message = 'Loading...', fullPage = false }) {
  return (
    <div
      className={`loading-state d-flex flex-column align-items-center justify-content-center gap-3${fullPage ? ' loading-state-full-page' : ''}`}
      role="status"
      aria-live="polite"
    >
      <div className="spinner-border text-primary" aria-hidden="true" />
      <span className="text-secondary">{message}</span>
    </div>
  );
}

export function EmptyState({ title, message }) {
  return (
    <div className="empty-state rounded-4 p-4 text-center">
      <div className="empty-state-icon mx-auto mb-3" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm2.5 7a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm9 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM8 15.5h8v-2H8v2Z" />
        </svg>
      </div>
      <h6 className="fw-bold text-dark mb-2">{title}</h6>
      <p className="text-secondary small mb-0">{message}</p>
    </div>
  );
}

export function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  loading = false,
  onConfirm,
  onCancel,
}) {
  const cancelButtonRef = useRef(null);

  useEffect(() => {
    if (!open) return undefined;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    cancelButtonRef.current?.focus();

    const closeOnEscape = (event) => {
      if (event.key === 'Escape' && !loading) onCancel();
    };

    window.addEventListener('keydown', closeOnEscape);

    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener('keydown', closeOnEscape);
    };
  }, [loading, onCancel, open]);

  if (!open) return null;

  return (
    <>
      <div className="modal-backdrop fade show" />
      <div
        className="modal fade show d-block"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirmation-dialog-title"
        aria-describedby="confirmation-dialog-message"
        onMouseDown={(event) => {
          if (event.target === event.currentTarget && !loading) onCancel();
        }}
      >
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content border-0 rounded-4 shadow-lg">
            <div className="modal-header border-0 pb-2">
              <h5 id="confirmation-dialog-title" className="modal-title fw-bold">{title}</h5>
              <button
                type="button"
                className="btn-close"
                aria-label="Close confirmation"
                onClick={onCancel}
                disabled={loading}
              />
            </div>
            <div className="modal-body pt-1">
              <p id="confirmation-dialog-message" className="text-secondary mb-0">{message}</p>
            </div>
            <div className="modal-footer border-0 pt-2">
              <button
                ref={cancelButtonRef}
                type="button"
                className="btn btn-outline-secondary rounded-pill px-4"
                onClick={onCancel}
                disabled={loading}
              >
                {cancelLabel}
              </button>
              <button
                type="button"
                className="btn btn-danger rounded-pill px-4"
                onClick={onConfirm}
                disabled={loading}
                aria-busy={loading}
              >
                {loading && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
                {loading ? 'Deleting...' : confirmLabel}
              </button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
