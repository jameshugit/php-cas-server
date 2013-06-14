version = `git describe --abbrev=0 2> /dev/null | cut -f2 -d'v'` 
version =  '0.0.0' if version.empty?

path = File.expand_path('../', __FILE__)

Gem::Specification.new do |s|
  s.name        = 'get-last-tweet'
  s.version     = version
  s.date        = Time.now.strftime('%Y-%m-%d')
  s.authors     = ['Michel Blanc']
  s.email       = ['mblanc@erasme.org']
  s.summary     = 'Gets last tweet from @user containing #hashtag and insert it in Redis'
  s.homepage    = 'http://github.com/'
  s.description = s.summary

  s.required_rubygems_version = '>= 1.3.5'
  s.files                     = Dir['bin/*']
  s.executables               = ['get-last-tweet']

  s.add_dependency 'twitter'
  s.add_dependency 'redis'
end
