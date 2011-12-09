#!/usr/bin/env ruby

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

require 'twitter'
require 'logger'
require 'json_builder'
require 'redis'

$logger = Logger.new(STDERR)
$logger.level = Logger::ERROR

user = nil
hashtag = nil
keyroot = nil

def log_and_exit(message)
  # logs a message with ERROR level and exists app
  # @param the error [message] to write to log
  $logger.error message 
  $logger.close
  exit
end

# checks that a file name is present at invocation
# and that this file exists
log_and_exit "Error : you must pass a config file as argument" if (ARGV[0].nil?)
log_and_exit "Error : unable to open config file #{ARGV[0]}" unless (File.file? ARGV[0])

# open config file given as argument on command line
open(ARGV[0]).each do |line|
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

# croak and exit if no user or hash...
log_and_exit "Error : unable to find hashtag, user and key root in config file" if (user.nil? or hashtag.nil? or keyroot.nil?)
# ... or if there is no @ prefix in front of user
log_and_exit "Error : user must be prefixed with '@'" unless (user.gsub('^@'))
# ... or of there is no # prefix in front of the hashtag
log_and_exit "Error : hashtag must be prefixed with '#'" unless (hashtag.gsub('^#'))

# twitter search doesn't want @ in front of users
# so we need to remove it
user.gsub!('@','')

# we build the twitter API query
query = "from:#{user} #{hashtag}"

# log it, if someone is interested
$logger.info "query : #{query}"

# and then search
begin
  twitt = Twitter.search(query, :rpp => 1).first
rescue Exception => e
  $logger.error e.message
#  $logger.error e.backtrace.join("\n")
  exit
end

if twitt.nil?
  # no match, no problem
  # we just wanr about it and exit
  $logger.warn "No twitt found"
  exit
end

# some information for the caring developper
$logger.info "Found twitt : #{twitt.text}"

# well, this is prolly useless
# it comes from an epic battle with memcached
# to get the strin gproperly encoded (and clean)
# which is something that never happened.
# Memcached won't, but was kicked out in favor of redis
# something that was going to happen anyway
twitt.text.encode!('ISO-8859-1')
text = twitt.text

date = "#{twitt.created_at.day}/#{twitt.created_at.month}/#{twitt.created_at.year}"

# ha yeah, this vvvvv was the memcached stuff, see how that sucked
# dc = Dalli::Client.new('127.0.0.1:11211', :expires_in => 15*86400)
# well, it's not obvious but IT REALLY SUCKED and ruined my day

# and now, Redis, see how that rules
dc = Redis.new
dc.set "#{keyroot}.text", text.to_json
dc.set "#{keyroot}.date", date
dc.expire("#{keyroot}.text", 15*86400)
dc.expire("#{keyroot}.date", 15*86400)


