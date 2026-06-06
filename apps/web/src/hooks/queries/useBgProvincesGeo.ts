import { useQuery } from '@tanstack/react-query';
import type { FeatureCollection, Geometry } from 'geojson';
import { http } from '@/lib/http';

export type ProvincesGeo = FeatureCollection<Geometry, { NUTS_ID: string }>;

/** The committed Bulgaria-oblasti GeoJSON (public/geo, sourced from yurukov/Bulgaria-geocoding).
 *  Fetched through the http wrapper with the /api baseURL overridden so it hits the static
 *  asset. Cached forever — the shapes never change in a session. */
export function useBgProvincesGeo() {
  return useQuery({
    queryKey: ['geo', 'bg-provinces'],
    queryFn: async () => {
      const response = await http.get<ProvincesGeo>('/geo/bg-provinces.geojson', { baseURL: '' });
      return response.data;
    },
    staleTime: Infinity,
    gcTime: Infinity,
  });
}
