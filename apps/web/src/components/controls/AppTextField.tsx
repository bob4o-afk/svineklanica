import { TextField, type TextFieldProps } from '@mui/material';
import { forwardRef } from 'react';

export type AppTextFieldProps = TextFieldProps;

/** The one text-input wrapper (login, source forms, review edits). Small + full-width by default
 *  so forms line up; callers pass `label`, `type`, `multiline`, etc. through. A `label` is
 *  required for accessibility — every field is labelled, never a bare placeholder. */
export const AppTextField = forwardRef<HTMLDivElement, AppTextFieldProps>(function AppTextField(
  { size = 'small', fullWidth = true, ...rest },
  ref,
) {
  return <TextField ref={ref} size={size} fullWidth={fullWidth} {...rest} />;
});
