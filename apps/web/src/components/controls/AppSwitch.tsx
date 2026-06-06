import { FormControlLabel, Switch } from '@mui/material';

export interface AppSwitchProps {
  label: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
  disabled?: boolean;
}

/** Labelled on/off toggle (e.g. a source's „активен" flag). The label is the accessible name;
 *  the caller owns the boolean. */
export function AppSwitch({ label, checked, onChange, disabled = false }: AppSwitchProps) {
  return (
    <FormControlLabel
      control={
        <Switch checked={checked} onChange={(event) => onChange(event.target.checked)} disabled={disabled} />
      }
      label={label}
    />
  );
}
