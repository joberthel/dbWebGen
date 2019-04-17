drop table if exists users cascade;
create table users (
	id serial primary key,
	name varchar(50) not null,
	login varchar(10) unique not null,
	password char(32) not null
);
insert into users(name, login, password) values('Demo', 'test', md5('test'));

drop table if exists activities cascade;
create table activities (
	id serial primary key ,
	name varchar(100) not null,
	duration varchar(100) not null
);
