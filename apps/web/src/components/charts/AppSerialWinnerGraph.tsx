import { Box } from '@mui/material';
import { useTheme } from '@mui/material/styles';
import { Background, Controls, type Edge, type Node, Position, ReactFlow } from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import type { CSSProperties } from 'react';
import { useMemo } from 'react';
import { fonts } from '@/theme/typography';
import { palette } from '@/theme/tokens';
import type { SerialWinnerGraph } from '@/types/api';

export interface AppSerialWinnerGraphProps {
  graph: SerialWinnerGraph;
  height?: number;
}

/** Serial-winner network: companies (left, alarm-red, sized hint by wins) → authorities
 *  (right). Shell-cluster members get a dashed rust border. Bipartite layout + React Flow
 *  pan/zoom. Themed for light + dark via the MUI theme. */
export function AppSerialWinnerGraph({ graph, height = 480 }: AppSerialWinnerGraphProps) {
  const theme = useTheme();

  const { nodes, edges } = useMemo(() => {
    const companies = graph.nodes.filter((n) => n.kind === 'company');
    const authorities = graph.nodes.filter((n) => n.kind === 'authority');
    const Y_STEP = 120;

    const rfNodes: Node[] = graph.nodes.map((n) => {
      const isCompany = n.kind === 'company';
      const index = (isCompany ? companies : authorities).indexOf(n);
      const isShell = n.cluster_id !== undefined;
      const style: CSSProperties = {
        background: isCompany ? palette.alarm : theme.palette.background.paper,
        color: isCompany ? '#ffffff' : theme.palette.text.primary,
        border: isShell
          ? `3px dashed ${palette.rust}`
          : `1px solid ${isCompany ? palette.alarm : theme.palette.divider}`,
        borderRadius: 2,
        padding: '8px 12px',
        width: 160,
        fontFamily: fonts.mono,
        fontSize: 12,
        fontWeight: 700,
        textAlign: 'center',
        whiteSpace: 'pre-line',
      };
      const label = n.win_count !== undefined ? `${n.label}\n● ${n.win_count}` : n.label;
      return {
        id: n.id,
        position: { x: isCompany ? 0 : 380, y: index * Y_STEP },
        data: { label },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
        style,
      };
    });

    const rfEdges: Edge[] = graph.edges.map((e) => ({
      id: e.id,
      source: e.source,
      target: e.target,
      animated: true,
      style: { stroke: palette.alarm, strokeWidth: Math.max(1, Math.min(6, e.weight)) },
      labelStyle: { fontFamily: fonts.mono, fontSize: 11, fontWeight: 700 },
      ...(e.label !== undefined ? { label: e.label } : {}),
    }));

    return { nodes: rfNodes, edges: rfEdges };
  }, [graph, theme]);

  return (
    <Box sx={{ height, width: '100%', borderRadius: 1, overflow: 'hidden' }}>
      <ReactFlow
        nodes={nodes}
        edges={edges}
        colorMode={theme.palette.mode}
        fitView
        fitViewOptions={{ padding: 0.2 }}
        nodesConnectable={false}
        edgesFocusable={false}
      >
        <Background />
        <Controls showInteractive={false} />
      </ReactFlow>
    </Box>
  );
}
