import { MenuItem, TextField } from '@mui/material';

export interface AppSelectOption {
  value: string;
  label: string;
}

export interface AppSelectProps {
  id: string;
  label: string;
  value: string;
  options: AppSelectOption[];
  onChange: (value: string) => void;
}

/** Compact labelled select (e.g. feed sort). Value is a plain string the caller narrows. */
export function AppSelect({ id, label, value, options, onChange }: AppSelectProps) {
  return (
    <TextField
      id={id}
      select
      size="small"
      label={label}
      value={value}
      onChange={(event) => onChange(event.target.value)}
      sx={{ minWidth: 160 }}
    >
      {options.map((option) => (
        <MenuItem key={option.value} value={option.value}>
          {option.label}
        </MenuItem>
      ))}
    </TextField>
  );
}
