#!/usr/bin/env ruby
# encoding: utf-8

require 'net/http'
require 'logger'
require 'json'
require 'json_builder'
require 'redis'
require 'optparse'
#
##
# 
# get-last-twitt.rb
#
# Retrieves last twitt sent by a specific @user and
# containing a specific #hashtag
#
# usage : get-last-twitt.rb @user #hashtag
# (arguments can come in any order)
#
# Deploying :
#
# adduser twitter
# apt-get install libreadline-dev
# su - twitter
# bash < <(curl -s https://raw.github.com/wayneeseguin/rvm/master/binscripts/rvm-installer)
# source ~/.bashrc
# rvm pkg install readline
# rvm install ruby-1.9.3
# rvm use 1.9.3@twitter --create --default
# bundle install
# 
# then put in twitter's crontab :
#
# SHELL=/bin/bash
# */30 * * * * source "$HOME/.rvm/scripts/rvm" && /some/path/get-last-twitt.rb /configfile/path
#

$logger = Logger.new(STDERR)
$logger.level = Logger::ERROR

user = nil
hashtag = nil
keyroot = nil

config_file = nil
redis_server = redis_port = nil

def log_and_exit(message)
  # logs a message with ERROR level and exists app
  # @param the error [message] to write to log
  $logger.error message 
  $logger.close
  exit
end


optparse = OptionParser.new do |opts|
  opts.banner = "Usage: get-last-twitt.rb -c <cas-config-file> -s <redis server> -p <redis port>"

  opts.on('-c', '--config [FILE]', 'Sets CAS server config [FILE]') do |f|
    config_file = f
  end

  opts.on('-s', '--server [SERVER]', 'Sets Redis [SERVER] hostname') do |s|
    redis_server = s
  end

  opts.on('-p', '--port [PORT]', 'Sets Redis server [PORT]') do |p|
    redis_port = p
  end

  opts.on_tail('-h', '--help', 'Show help') do
    puts opts
    exit
  end
end

optparse.parse!

# checks that a file name is present at invocation
# and that this file exists
log_and_exit "Error : you must pass a config file as argument" unless config_file
log_and_exit "Error : unable to open config file #{config_file}" unless (File.file?(config_file))
log_and_exit "Error : you must set the redis server with -s" unless redis_server
log_and_exit "Error : you must set the redis port with -p" unless redis_port

# open config file given as argument on command line
open(config_file).each do |line|
  # loop thru config file
  line.chomp!
  $logger.debug("config : #{line}")
  begin
    if line.match('TWITTER_ACCOUNT') then
      # we have found an interesting parameter
      # let's grab the config value and store it in 'user'
      user = line.match(".*'TWITTER_ACCOUNT'.*'(.*)'")[1];
      $logger.info("found a match for TWITTER_ACCOUNT : #{user}")
    end

    if line.match('TWITTER_HASHTAG') then
      # we have found an interesting parameter
      # let's grab the config value and store it in 'hashtag'
      hashtag = line.match(".*'TWITTER_HASHTAG'.*'(.*)'")[1];
      $logger.info("found a match for TWITTER_HASHTAG : #{hashtag}")
    end

    if line.match('REDIS_NEWS_ROOT') then
      # we have found an interesting parameter
      # let's grab the config value and store it in 'hashtag'
      keyroot = line.match(".*'REDIS_NEWS_ROOT'.*'(.*)'")[1];
      $logger.info("found a match for REDIS_NEWS_ROOT : #{keyroot}")
    end

  rescue
    # this is quite needed if we match a string
    # but \1 is nil
    # we handle this case below
  end
end


$logger.info("Using redis server : #{redis_server}:#{redis_port}")

# croak and exit if no user or hash...
log_and_exit "Error : unable to find hashtag, user and key root in config file" if (user.nil? or hashtag.nil? or keyroot.nil?)
# ... or if there is no @ prefix in front of user
log_and_exit "Error : user must be prefixed with '@'" unless (user.gsub('^@'))
# ... or if there is no # prefix in front of the hashtag
log_and_exit "Error : hashtag must be prefixed with '#'" unless (hashtag.gsub('^#'))

# twitter search doesn't want @ in front of users
# so we need to remove it
user.gsub!('@','')

# we build the twitter API query
q = "from:#{user} #{hashtag}"

# log it, if someone is interested
$logger.info "query : #{q}"

params = { :q => q,
           :rpp => 1,
           :include_entities => true,
           :result_type => "recent" }

uri = URI("http://search.twitter.com/search.json")

uri.query = URI.encode_www_form(params)

res = Net::HTTP.get_response(uri)

if res.is_a?(Net::HTTPSuccess)
  twitt = JSON.parse(res.body)
else
  $logger.error "Error : %s %s %s" % [res.code, res.message, res.class.name]
  exit
end

if twitt.nil?
  # no match, no problem
  # we just wanr about it and exit
  $logger.warn "No twitt found"
  exit
end

twitt = twitt['results'].first
text = twitt['text']
# some information for the caring developper
$logger.info "Found twitt : #{text}"

# well, this is prolly useless
# it comes from an epic battle with memcached
# to get the strin gproperly encoded (and clean)
# which is something that never happened.
# Memcached won't, but was kicked out in favor of redis
# something that was going to happen anyway
text.encode!('ISO-8859-1')

(day, month, year) = twitt['created_at'].split(' ')[1..3]

month = [ 'Jan', 'Feb', 'Mar','Apr','May','Jun','Jul','Aug','Sep','Nov','Dec' ].index(month) + 1

date = "#{day}/#{month}/#{year}"

dc = Redis.new(:host => redis_server, :port => redis_port)

dc.set "#{keyroot}text", text.to_json
dc.set "#{keyroot}date", date
dc.expire("#{keyroot}text", 15*86400)
dc.expire("#{keyroot}date", 15*86400)


