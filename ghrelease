#!/bin/sh
#
# First try at a release script...
#
set -euf -o pipefail

checks="wfscripts/checks"

rc=false
github=false
gh_auth=false
if type gh >/dev/null 2>&1 ; then
  if gh auth status >/dev/null 2>&1 ; then
    gh_auth=true
    github=true
  fi
fi


while [ $# -gt 0 ]
do
  case "$1" in
    --rc|-t) rc=true ;;
    --rel|-r) rc=false ;;
    --gh|-g) github=true ;;
    --no-gh|-G) github=false ;;
    *) break ;;
  esac
  shift
done

if [ $# -lt 1 ] ; then
  cat <<-_EOF_
	Usage: $0 [options] version

	Options:
	* --rc|-t : create a release candidate (test release)
	* --rel|-r : create a release
	* --gh|-g : use github API
	* --no-gh|-G : do not use github API
	* version : version tag

	If version is --purge, it will delete all pre-releases
	_EOF_
  exit 1
fi

if $github ; then
  if ! gh auth status ; then
    exit 2
  fi
fi

relid="$1" ; shift
repodir="$(dirname "$(readlink -f "$0")")"
cd "$repodir"

git pull --tags # Make sure remote|local tags are in sync

if [ x"$relid" = x"--purge" ] ; then
  # Remove pre-release versions...
  if $github ; then
    gh release list | awk '$2 == "Pre-release" { print $1 }' | while read vtag
    do
      gh release delete $vtag --yes || :
      git tag -d $vtag || :
      git push --delete origin $vtag || :
    done
  else
    echo "You can only purge from github releases"
  fi
  exit
fi

if [ -n "$(git tag -l $relid)" ] ; then
  echo "Tag: \"$relid\" already exists!"
  gh release list
  exit 5
fi

cbranch=$(git rev-parse --abbrev-ref HEAD)
dbranch=$(basename "$(git rev-parse --abbrev-ref origin/HEAD)")

if $rc ; then
  echo "Release candidate: $relid"
else
  if [ x"$cbranch" != x"$dbranch" ] ; then
    echo "Current branch is \"$cbranch\""
    echo "Releases can only be done from the default branch: \"$dbranch\""
    echo "Switch to the default branch or use the --rc (release candidate) option"
    exit 2
  fi
fi

# Check for uncomitted changes
if [ -n "$(git status --porcelain)" ] ; then
  echo "Only run this on a clean checkout"
  echo ''
  git status
  exit 3
fi

if [ -d "$checks" ] ; then
  run-parts "$checks"
fi

if ptag=$(git describe --abbrev=0) ; then
  relnotes="$(git log "$ptag"..HEAD)" # --oneline
else
  relnotes="$(git log HEAD)" # --oneline
fi
if [ -z "$relnotes" ] ; then
  echo "No commits since last release"
  exit 4
fi

if [ -f "version.h" ] ; then
  vfile="version.h"
  vformat='const char version[] = "%s";\n'
else
  vformat='%s\n'
  vfile=VERSION
fi

printf "$vformat" "$relid" > "$vfile"
git add "$vfile"
git commit -m "$relid" "$vfile"
git tag -a "$relid" -m "$relid"
git push
git push --tags

gh release create \
	"$relid" \
	$($rc && echo --prerelease) \
	--target "$cbranch" \
	--title "$relid" \
	--notes "$relnotes"
