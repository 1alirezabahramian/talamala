import type { ReactNode } from 'react';

export type FormFieldProps = {
  id: string;
  label: string;
  hint?: string;
  error?: string | null;
  children: ReactNode;
};

/** Label + optional hint/error around a control. No business logic. */
export function FormField(props: FormFieldProps) {
  return (
    <div className="tal-field">
      <label htmlFor={props.id}>{props.label}</label>
      {props.children}
      {props.hint && !props.error ? <p className="tal-hint">{props.hint}</p> : null}
      {props.error ? (
        <p className="tal-field-error" role="alert">
          {props.error}
        </p>
      ) : null}
    </div>
  );
}
