import { IconButton, InputAdornment, TextField } from '@mui/material';
import { MagnifyingGlassIcon, XIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';

export interface AppSearchInputProps {
  value: string;
  onChange: (value: string) => void;
  /** Called on Enter. */
  onSubmit?: (value: string) => void;
  autoFocus?: boolean;
}

/** The search box: a labelled text field with a search icon and a clear button. The caller owns
 *  the value (debounce / URL sync happens there). */
export function AppSearchInput({ value, onChange, onSubmit, autoFocus = false }: AppSearchInputProps) {
  const { t } = useTranslation();

  return (
    <TextField
      fullWidth
      size="small"
      value={value}
      autoFocus={autoFocus}
      label={t('search:placeholder')}
      onChange={(event) => onChange(event.target.value)}
      onKeyDown={(event) => {
        if (event.key === 'Enter' && onSubmit !== undefined) onSubmit(value);
      }}
      slotProps={{
        input: {
          startAdornment: (
            <InputAdornment position="start">
              <MagnifyingGlassIcon size={18} />
            </InputAdornment>
          ),
          ...(value !== ''
            ? {
                endAdornment: (
                  <InputAdornment position="end">
                    <IconButton size="small" aria-label={t('search:clear')} onClick={() => onChange('')}>
                      <XIcon size={16} />
                    </IconButton>
                  </InputAdornment>
                ),
              }
            : {}),
        },
      }}
    />
  );
}
