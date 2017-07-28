# Changelog
<!-- Uses format from https://github.com/olivierlacan/keep-a-changelog/blob/master/CHANGELOG.md -->
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

Note that changes backward-incompatibile for subclasses extending `Shape` will
**not** lead to a bump in the major version.

## [Unreleased] (2.0.0)
### Added
- `Shape::marginalDistance()` (formerly in `LazyStreamsShape`)
- `LibgeomBinaryStream` for saving shapes
- `Shape::getChunksInvolved()`
- shape saving through `Shape::fromBinary()` and `Shape::toBinary()`

### Changed
- virion.yml limits the accepted API versions to `3.0.0-ALPHA6` and `3.0.0-ALPHA7`
- `BlockStream` is replaced by [`Generator`](https://php.net/generator) with method signature changes
- A major namespace refactor

### Removed
- Unused classes:

## 1.0.0 2017-07-20
### Removed
- The whole `blockop` package.

[Unreleased]: https://github.com/BlockHorizons/libgeom/compare/v1.0.0...HEAD
