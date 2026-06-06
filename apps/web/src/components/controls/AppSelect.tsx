import { FormControl, InputLabel, MenuItem, Select } from '@mui/material';

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

/** Compact labelled select (e.g. feed sort). Uses InputLabel `id` + Select `labelId`
 *  (aria-labelledby) rather than `<label for>` — for a select the control is a div, so a `for`
 *  attribute can't reference it and the browser warns. Value is a plain string the caller narrows. */
export function AppSelect({ id, label, value, options, onChange }: AppSelectProps) {
  const labelId = `${id}-label`;
  return (
    <FormControl size="small" sx={{ minWidth: 160 }}>
      <InputLabel id={labelId}>{label}</InputLabel>
      <Select
        labelId={labelId}
        id={id}
        value={value}
        label={label}
        onChange={(event) => onChange(event.target.value)}
      >
        {options.map((option) => (
          <MenuItem key={option.value} value={option.value}>
            {option.label}
          </MenuItem>
        ))}
      </Select>
    </FormControl>
  );
}
