// Basic mesh diagnostics, independent of Three.js and usable with indexed STL/3MF geometry.
// Coordinate welding uses a fixed 0.00001 mm tolerance; this is not mesh repair.
export function analyzeMesh(position, index = null, maxTriangles = 200000) {
  const count = index ? index.count : position.count;
  const triangles = Math.floor(count / 3);
  const result = { checked: 1, triangles, boundaryEdges: 0, nonManifoldEdges: 0,
    windingEdges: 0, degenerateTriangles: 0, duplicateTriangles: 0, invalidTriangles: 0,
    topologySkipped: triangles > maxTriangles ? 1 : 0 };
  if (count === 0 || count % 3 !== 0) result.invalidTriangles++;
  const vertices = new Map(), edges = new Map(), faces = new Set();
  const vertex = i => {
    if (!Number.isInteger(i) || i < 0 || i >= position.count) return null;
    const point = [position.getX(i), position.getY(i), position.getZ(i)];
    if (!point.every(Number.isFinite)) return null;
    const key = point.map(v => Math.round(v / 0.00001)).join(',');
    if (!vertices.has(key)) vertices.set(key, vertices.size);
    return vertices.get(key);
  };
  // Never sample topology: a sampled surface would produce false holes.
  if (result.topologySkipped) return result;
  for (let t = 0; t < triangles; t++) {
    const raw = [0, 1, 2].map(k => index ? index.getX(t * 3 + k) : t * 3 + k);
    const ids = raw.map(vertex);
    if (ids.includes(null)) { result.invalidTriangles++; continue; }
    const [a, b, c] = raw.map(i => [position.getX(i), position.getY(i), position.getZ(i)]);
    const u = b.map((v, i) => v - a[i]), v = c.map((x, i) => x - a[i]);
    const cross = [u[1]*v[2]-u[2]*v[1], u[2]*v[0]-u[0]*v[2], u[0]*v[1]-u[1]*v[0]];
    if (new Set(ids).size < 3 || Math.hypot(...cross) <= 1e-10) { result.degenerateTriangles++; continue; }
    const face = [...ids].sort((a,b) => a-b).join(':');
    if (faces.has(face)) result.duplicateTriangles++;
    faces.add(face);
    for (let i = 0; i < 3; i++) {
      const a = ids[i], b = ids[(i + 1) % 3];
      const key = a < b ? `${a}:${b}` : `${b}:${a}`;
      const edge = edges.get(key) || { count: 0, direction: 0 };
      edge.count++; edge.direction += a < b ? 1 : -1;
      edges.set(key, edge);
    }
  }
  for (const edge of edges.values()) {
    if (edge.count === 1) result.boundaryEdges++;
    else if (edge.count > 2) result.nonManifoldEdges++;
    else if (edge.direction !== 0) result.windingEdges++;
  }
  return result;
}

export function exactDuplicate(files, hash) {
  return hash ? files.find(file => file.hash === hash) || null : null;
}

export function threeMFUnitScale(unit = 'millimeter') {
  return ({ micron: 0.001, millimeter: 1, centimeter: 10, inch: 25.4, foot: 304.8, meter: 1000 })[unit] ?? null;
}

export function remapProject(project, fileIdMap, id) {
  return { ...project, id, parts: project.parts.map(part => {
    const fileId = fileIdMap.get(part.fileId);
    if (!fileId) throw new Error('Een projectonderdeel ontbreekt in de backup.');
    return { ...part, fileId };
  }) };
}
