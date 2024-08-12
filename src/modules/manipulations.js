// #region Paths

export const concatPaths = (...paths) => {
  return paths
    .map((path, index) => {
      // Remove leading slashes from all segments except the first
      if (index > 0) {
        path = path.replace(/^\//, '')
      }
      // Remove trailing slashes from all segments
      return path.replace(/\/$/, '')
    })
    .join('/')
}

// #endregion
