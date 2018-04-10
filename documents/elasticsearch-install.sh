#!/usr/bin/env bash

VERSION=5.6.4
es_memory='4g'

apt-get install openjdk-8-jdk
rm /etc/alternatives/java
ln -s /usr/lib/jvm/java-8-openjdk-amd64/jre/bin/java /etc/alternatives/java

export JAVA_HOME=/usr/lib/jvm/java-8-openjdk-amd64
export PATH=$JAVA_HOME/bin:$PATH

if [ ! -f elasticsearch-$VERSION.deb ]; then
  wget https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-$VERSION.deb
fi

sudo dpkg -i elasticsearch-$VERSION.deb

# be sure you add "action.disable_delete_all_indices" : true to the config!!

info "Configuring ElasticSearch 5"

sed -i "/LimitFSIZE=infinity/a LimitMEMLOCK=infinity" /usr/lib/systemd/system/elasticsearch.service
sed -i "s/#bootstrap.memory_lock: true/bootstrap.memory_lock: true/" /etc/elasticsearch/elasticsearch.yml
sed -i "s/#MAX_LOCKED_MEMORY=unlimited/MAX_LOCKED_MEMORY=unlimited/" /etc/default/elasticsearch
sed -i "s/-Xms2g/-Xms$es_memory/" /etc/elasticsearch/jvm.options
sed -i "s/-Xmx2g/-Xmx$es_memory/" /etc/elasticsearch/jvm.options

# enabled
/lib/systemd/systemd-sysv-install enable elasticsearch

# start script
sudo /etc/init.d/elasticsearch restart

if [ -f /usr/bin/plugin ]; then
  rm /usr/bin/plugin
fi

rm /usr/bin/plugin
sudo ln -s /usr/share/elasticsearch/bin/plugin /usr/bin/plugin